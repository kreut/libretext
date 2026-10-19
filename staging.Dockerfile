FROM laravelphp/vapor:php80
# poppler-utils: pdftoppm renders submission PDFs to page images (ConvertSubmissionPdfToImages).
# fontconfig + fonts: PDFs that use standard fonts without embedding them (Times, Helvetica...) otherwise
# render with no text. Liberation matches the metrics of Times/Arial/Courier; DejaVu covers other characters.
# On newer Alpine these are named font-liberation and font-dejavu.
RUN apk --update add ffmpeg poppler-utils fontconfig ttf-liberation ttf-dejavu
COPY . /var/task
