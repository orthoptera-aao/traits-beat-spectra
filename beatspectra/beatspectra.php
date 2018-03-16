<?php

function beatspectra_info() {
  return(
    array(
      "beatspectra" => array(
        "dependencies" => array("bioacoustica") //BioAcoustica provides wave file.
      )
    )
  );
}

function beatspectra_init() {
  $init = array(
    "R" => array(
      "type" => "cmd",
      "required" => "required",
      "missing text" => "beatspectra requires R.",
      "version flag" => "--version"
    ),
    "data.table" => array( 
      "type" => "Rpackage",
      "required" => "required",
      "missing text" => "beatspectra requires the R data.table package.",
      "version flag" => "--quiet -e 'packageVersion(\"data.table\")'",
      "version line" => 1
    ),
    "WaveletComp" => array( 
      "type" => "Rpackage",
      "required" => "required",
      "missing text" => "beatspectra requires the R WaveletComp package.",
      "version flag" => "--quiet -e 'packageVersion(\"WaveletComp\")'",
      "version line" => 1
    ),
    "phonTools" => array( 
      "type" => "Rpackage",
      "required" => "required",
      "missing text" => "beatspectra requires the R phonTools package.",
      "version flag" => "--quiet -e 'packageVersion(\"phonTools\")'",
      "version line" => 1
    ),
    "seewave" => array( 
      "type" => "Rpackage",
      "required" => "required",
      "missing text" => "beatspectra requires the R seewave package.",
      "version flag" => "--quiet -e 'packageVersion(\"seewave\")'",
      "version line" => 1
    )
  );
  return ($init);
}

function beatspectra_prepare() {
  global $system;
  core_log("info", "beatspectra", "Attempting to list beatspectragram files on analysis server.");
  exec("s3cmd ls s3://bioacoustica-analysis/beatspectra/".$system["modules"]["beatspectra"]["git_hash"]."/", $output, $return_value);
  if ($return_value == 0) {
    if (count($output) == 0) {
      $system["analyses"]["beatspectra"] = array();
    } else {
      foreach ($output as $line) {
        $start = strrpos($line, "/");
        $system["analyses"]["beatspectra"][] = substr($line, $start + 1);
      }
    }
  core_log("info", "beatspectra", count($system["analyses"]["beatspectra"])." beatspectra files found.");
  }
  return(array());
}

function beatspectra_analyse($recording) {
  global $system;
  $return = array();
  if (!in_array($recording["id"].".png", $system["analyses"]["beatspectra"])) {
    $file = core_download("wav/".$recording["id"].".1kHz-highpass.wav");
    if ($file == NULL) {
      core_log("warning", "beatspectra", "File was not available, skipping analysis.");
      return($return);
    }
    $return[$recording["id"].".1kHz-highpass.wav"] = array(
      "file name" => $recording["id"].".1kHz-highpass.wav",
      "local path" => "scratch/wav/",
      "save path" => NULL
    );
    core_log("info", "beatspectra", "Attepting to create beatspectragram for recording ".$recording["id"].".");
    exec("Rscript modules/traits-beatspectra/beatspectra/beatspectra.R ".$recording["id"]." scratch/wav/".$recording["id"].".wav", $output, $return_value);
    if ($return_value == 0) {
      $return[$recording["id"].".png"] = array(
        "file name" => $recording["id"].".png",
        "local path" => "modules/traits-beatspectra/beatspectra/",
        "save path" => "beatspectra/".$system["modules"]["beatspectra"]["git_hash"]."/"
      );
      $return[$recording["id"].".csv"] = array(
        "file name" => $recording["id"].".csv",
        "local path" => "modules/traits-beatspectra/beatspectra/",
        "save path" => "beatspectra/".$system["modules"]["beatspectra"]["git_hash"]."/"
      );
      $return[$recording["id"].".largest-peak-data.csv"] = array(
        "file name" => $recording["id"].".largest-peak-data.csv",
        "local path" => "modules/traits-beatspectra/beatspectra/",
        "save path" => "beatspectra/".$system["modules"]["beatspectra"]["git_hash"]."/"
      );
    } else {
      core_log("warning", "beatspectra", "Recording ".$recording["id"].": Failed to read wave file: ".serialize($output));
      }
    }
  }
  return($return);
}
