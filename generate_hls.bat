@echo off
mkdir assets\hls 2>nul
ffmpeg -i assets/smice2.mp4 -codec: copy -start_number 0 -hls_time 10 -hls_list_size 0 -f hls assets/hls/playlist.m3u8
echo HLS generation complete.
pause
