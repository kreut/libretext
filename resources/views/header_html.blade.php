<html>
<head>
  <link rel="stylesheet" href="https://dev.adapt.libretexts.org/assets/css/libretext.css?v=2">
  <script type="text/javascript"
          src="https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/4.2.11/iframeResizer.contentWindow.min.js"
  ></script>
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
  <title>Header HTML</title>
</head>
<body>
{!! $non_technology_html!!}
<script type="text/x-mathjax-config">/*<![CDATA[*/
  MathJax.Ajax.config.path["mhchem"] =
            "https://cdnjs.cloudflare.com/ajax/libs/mathjax-mhchem/3.3.2";
        MathJax.Hub.Config({ messageStyle: "none",
        tex2jax: {preview: "none"},
        jax: ["input/TeX","input/MathML","output/SVG"],
  extensions: ["tex2jax.js","mml2jax.js","MathMenu.js","MathZoom.js"],
  TeX: {
        extensions: ["autobold.js","mhchem.js","color.js","cancel.js", "AMSmath.js","AMSsymbols.js","noErrors.js","noUndefined.js"]
  },
    "HTML-CSS": { linebreaks: { automatic: true , width: "90%"}, scale: 85, mtextFontInherit: false},
menuSettings: { zscale: "150%", zoom: "Double-Click" },
         SVG: { linebreaks: { automatic: true } }});
/*]]>*/

</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.3/MathJax.js?config=TeX-AMS_HTML"></script>
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

</script>
</body>
</html>

