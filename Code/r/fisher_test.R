#!/usr/bin/Rscript
setwd('/var/www/html/')
args <- commandArgs(TRUE)

start_time <- Sys.time()
in_path_db<-as.integer(args[1])   ## in_path_db
not_in_path_db<-as.integer(args[2])    ## not_in_path_db
in_path_list<-as.integer(args[3]) ## in_path_list
not_in_path_list<-as.integer(args[4]) ## not_in_path_list

data <-
  matrix(c(in_path_list, not_in_path_list, in_path_db, not_in_path_db),
         nrow = 2)
p<-fisher.test(data, alternative = "greater", )$p.value
end_time <- Sys.time()

time  <- (end_time-start_time)
##print(time)
cat(time)


##cat(p)
