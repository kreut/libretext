<html>
<head>
  <link href="{{ asset('assets/css/libretext.css?v=3') }}" rel="stylesheet">
  <script type="text/javascript"
          src="https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/4.2.11/iframeResizer.contentWindow.min.js"
  ></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/4.1.1/iframeResizer.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"
          integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg=="
          crossorigin="anonymous"
  ></script>
  <script type="text/javascript"
          src="https://cdn.libretexts.net/github/LibreTextsMain/Miscellaneous/Molecules/GLmol/js/Three49custom.js"
  ></script>
  <script type="text/javascript"
          src="https://cdn.libretexts.net/github/LibreTextsMain/Miscellaneous/Molecules/GLmol/js/GLmol.js"
  ></script>
  <script type="text/javascript"
          src="https://cdn.libretexts.net/github/LibreTextsMain/Miscellaneous/Molecules/JSmol/JSmol.full.nojq.js"
  ></script>
  <script type="text/javascript"
          src="https://cdn.libretexts.net/github/LibreTextsMain/Miscellaneous/Molecules/3Dmol/3Dmol-nojquery.js"
  ></script>
  <title>Open-ended Content</title>
</head>
<body>
<div id="non-technology-html">{!! $non_technology_html!!}</div>
<script>
  window.MathJax = {
    tex: {
      inlineMath: [['$', '$'], ['\\(', '\\)']],
      displayMath: [['$$', '$$'], ['\\[', '\\]']],
      packages: { '[+]': ['mhchem', 'cancel', 'color', 'noerrors'] },
      macros: {
        mhchemrightleftharpoons: "{\\unicode{x21CC}\\,}",
        xrightleftharpoons: ["\\mhchemxrightleftharpoons[#1]{#2}", 2, ""],
      },
    },
    loader: {
      load: ['[tex]/mhchem', '[tex]/cancel', '[tex]/noerrors'],
    },
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.2/es5/tex-mml-svg.min.js"></script>
<script>
  titleHolder = document.getElementById('titleHolder')
  let front
  if (titleHolder) {
    front = titleHolder.innerText
    front = front.match(/^.*?:/)
  }
  if (front) {
    front = front[0]
    front = front.split(':')[0]
    if (front.includes('.')) {
      front = front.split('.')
      front = front.map((int) => int.includes('0') ? parseInt(int, 10) : int).join('.')
    }
    front += '.'
  } else {
    front = ''
  }
  front = front.replace(/_/g, ' ')
  MathJaxConfig = {
    TeX: {
      equationNumbers: {
        autoNumber: 'all',
        formatNumber: function (n) {
          return front + n
        }
      },
      macros: {
        PageIndex: ['{' + front + ' #1}', 1],
        test: ['{' + front + ' #1}', 1]
      },
      Macros: {
        PageIndex: ['{' + front + ' #1}', 1],
        test: ['{' + front + ' #1}', 1]
      },
      SVG: {
        linebreaks: { automatic: true }
      }
    }
  }

  MathJax.Hub.Config(MathJaxConfig)
  const currentDomain = window.location.origin;
  const regex = new RegExp(`<a\\s+href="${currentDomain}/question-media-player/([^"]+)">([^<]+)<\\/a>`, 'g');
  const regex2 = new RegExp(`<a\\s+href="https:\\/\\/customer-([a-zA-Z0-9]+)\\.cloudflarestream\\.com\\/([a-zA-Z0-9]+)\\/iframe">([^<]+)<\\/a>`, 'g');

  const div = document.getElementById('non-technology-html');
  if (div) {
    div.innerHTML = div.innerHTML.replace(regex, (match, url, text) => {
      return `<iframe class="question-media-player" style="width: 1px;min-width: 100%;" frameborder="0" src="${currentDomain}/question-media-player/${url}"></iframe>`;
    });
    div.innerHTML = div.innerHTML.replace(regex2, (match, code, videoUid, text) => {
      return `<iframe style="width: 1px; min-width: 100%;" frameborder="0" src="https://customer-${code}.cloudflarestream.com/${videoUid}/iframe" loading="lazy" allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture;" allowfullscreen="true"></iframe>`;
    });
  } else {
    console.log('Element with id "non-technology-html" not found.');
  }
</script>
<script>
  const observer = new ResizeObserver(() => {
    if (window.parentIFrame) {
      window.parentIFrame.size()
    }
  })

  observer.observe(document.body)
</script>
</body>
</html>

