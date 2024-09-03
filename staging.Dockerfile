FROM laravelphp/vapor:php80
RUN apk --update add ffmpeg
COPY . /var/task
