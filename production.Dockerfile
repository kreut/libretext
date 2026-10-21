FROM laravelphp/vapor:php84
RUN apk add --no-cache ffmpeg poppler-utils fontconfig font-liberation font-dejavu
COPY . /var/task

