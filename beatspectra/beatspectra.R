library(data.table)
library(WaveletComp)
library(phonTools)
library(seewave)
library(tuneR)

args = commandArgs(trailingOnly=TRUE)
recording_id <- args[1]
filename <- args[2];

folder <- "modules/traits-beatspectra/beatspectra/"

wave <- readWave(filename)

wave<-ffilter(wave, from=1000, to=wave@samp.rate/2, output="Wave")
beatSpectrum <- function(wave, 
                         min_period = 5e-4,#s
                         max_period=30, #s,
                         dj=1/32, # 1/nvoices
                         ...
){
  scaling_ratio <- wave@samp.rate / (1/min_period)
  runmed_k <- 2*(floor(scaling_ratio/2))+1
  signal <- runmed(abs(wave@left), runmed_k)
  n = length(signal)
  t0 <- 0:(n-1) / wave@samp.rate
  t1 <- seq(from=0, to=t0[length(t0)], by=min_period)
  signal_dsp <- approx(y=signal,t0, xout = t1)$y
  dt_tmp <- data.table(x=signal_dsp)
  upper_period = ceiling(1 + log2(max_period/ min_period))
  wt <- analyze.wavelet(dt_tmp,"x",
                        loess.span = 0, dj=dj,
                        lowerPeriod = 2 ^ 1,
                        upperPeriod = 2 ^ upper_period,
                        make.pval =F, 
                        verbose = F,...)
  data.table(power=wt$Power.avg, period = wt$Period * min_period)
}

bs <- beatSpectrum(wave)

write.csv(bs, file=paste0(folder,recording_id,".csv"))

pos_peaks <- bs[peakfind(bs$power),]
neg_peaks_n <- peakfind(-1*bs$power, show=FALSE)
neg_peaks <- bs[neg_peaks_n,]

png(filename=paste0(folder,recording_id,".png"))
plot(bs$period, bs$power, log="x", type="l")
dev.off()


  max_value <- max(bs$power)
  max_pos <- which.max(bs$power)
  left <- right <- max_pos
  step <- 1
  while (left == max_pos || right == max_pos){
    if (bs$power[[max_pos - step]] < 0.5*max_value) {
      left <- max_pos - step
    }
    if (bs$power[[max_pos + step]] < 0.5*max_value) {
      right <- max_pos + step
    }
    step <- step + 1
  }

  peak_periodicty_width <- bs$period[[right]] - bs$period[[left]]

  data <- c(max_pos, bs$period[[max_pos]], peak_periodicty_width)
  names(data) <-c("peak location", "peak_periodicty", "peak_periodicty_width")

write.csv(data, file=paste0(folder,recording_id,".largest-peak-data.csv"), col.names=FALSE)

