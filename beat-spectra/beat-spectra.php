<?php

function beat-spectra_info() {
  return(
    array(
      "beat-spectra" => array(
        "dependencies" => array("bioacoustica") //BioAcoustica provides wave file.
      )
    )
  );
}

function beat-spectra_init() {
  $init = array(
    "R" => array(
      "type" => "cmd",
      "required" => "required",
      "missing text" => "beat-spectra requires R.",
      "version flag" => "--version"
    ),
    "data.table" => array( 
      "type" => "Rpackage",
      "required" => "required",
      "missing text" => "beat-spectra requires the R data.table package.",
      "version flag" => "--quiet -e 'packageVersion(\"data.table\")'",
      "version line" => 1
    ),
    "WaveletComp" => array( 
      "type" => "Rpackage",
      "required" => "required",
      "missing text" => "beat-spectra requires the R WaveletComp package.",
      "version flag" => "--quiet -e 'packageVersion(\"WaveletComp\")'",
      "version line" => 1
    ),
    "phonTools" => array( 
      "type" => "Rpackage",
      "required" => "required",
      "missing text" => "beat-spectra requires the R phonTools package.",
      "version flag" => "--quiet -e 'packageVersion(\"phonTools\")'",
      "version line" => 1
    ),
    "seewave" => array( 
      "type" => "Rpackage",
      "required" => "required",
      "missing text" => "beat-spectra requires the R seewave package.",
      "version flag" => "--quiet -e 'packageVersion(\"seewave\")'",
      "version line" => 1
    )
  );
  return ($init);
}

function beat-spectra_prepare() {
  global $system;
  core_log("info", "beat-spectra", "Attempting to list beat-spectragram files on analysis server.");
  exec("s3cmd ls s3://bioacoustica-analysis/beat-spectra/".$system["modules"]["beat-spectra"]["git_hash"]."/", $output, $return_value);
  if ($return_value == 0) {
    if (count($output) == 0) {
      $system["analyses"]["beat-spectra"] = array();
    } else {
      foreach ($output as $line) {
        $start = strrpos($line, "/");
        $system["analyses"]["beat-spectra"][] = substr($line, $start + 1);
      }
    }
  core_log("info", "beat-spectra", count($system["analyses"]["beat-spectra"])." beat-spectra files found.");
  }
  return(array());
}

function beat-spectra_analyse($recording) {
  global $system;
  $return = array();
  if (!in_array($recording["id"].".png", $system["analyses"]["beat-spectra"])) {
    $file = core_download("wav/".$recording["id"].".1kHz-highpass.wav");
    if ($file == NULL) {
      core_log("warning", "beat-spectra", "File was not available, skipping analysis.");
      return($return);
    }
    $return[$recording["id"].".1kHz-highpass.wav"] = array(
      "file name" => $recording["id"].".1kHz-highpass.wav",
      "local path" => "scratch/wav/",
      "save path" => NULL
    );
    core_log("info", "beat-spectra", "Attepting to create beat-spectragram for recording ".$recording["id"].".");
    exec("Rscript modules/traits-beat-spectra/beat-spectra/beat-spectra.R ".$recording["id"]." scratch/wav/".$recording["id"].".wav", $output, $return_value);
    if ($return_value == 0) {
      $return[$recording["id"].".png"] = array(
        "file name" => $recording["id"].".png",
        "local path" => "modules/traits-beat-spectra/beat-spectra/",
        "save path" => "beat-spectra/".$system["modules"]["beat-spectra"]["git_hash"]."/"
      );
      $return[$recording["id"].".csv"] = array(
        "file name" => $recording["id"].".csv",
        "local path" => "modules/traits-beat-spectra/beat-spectra/",
        "save path" => "beat-spectra/".$system["modules"]["beat-spectra"]["git_hash"]."/"
      );
      $return[$recording["id"].".largest-peak-data.csv"] = array(
        "file name" => $recording["id"].".largest-peak-data.csv",
        "local path" => "modules/traits-beat-spectra/beat-spectra/",
        "save path" => "beat-spectra/".$system["modules"]["beat-spectra"]["git_hash"]."/"
      );
    } else {
      core_log("warning", "beat-spectra", "Recording ".$recording["id"].": Failed to read wave file: ".serialize($output));
      }
    }
  }
  return($return);
}
