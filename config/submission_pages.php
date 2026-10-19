<?php

return [
    /*
     * Full path to pdftoppm (poppler-utils). On Vapor's Docker image plain "pdftoppm" is on the PATH.
     * Locally under MAMP, PHP may not search Homebrew's folder, so set e.g.
     * PDFTOPPM_PATH=/opt/homebrew/bin/pdftoppm   (Apple Silicon)
     * PDFTOPPM_PATH=/usr/local/bin/pdftoppm      (Intel)
     */
    'pdftoppm_path' => env('PDFTOPPM_PATH', 'pdftoppm'),
];
