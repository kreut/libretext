<link rel="stylesheet" href="https://adapt.libretexts.org/assets/css/libretext.css?v=2">
<script type="text/javascript" src="https://adapt.libretexts.org/assets/js/hostIFrameResizer.js"></script>
<?php
if (isset($extras['glMol'])  && $extras['glMol']) {
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"
            integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg=="
            crossorigin="anonymous"></script>
    <script type="text/javascript"
            src="https://cdn.libretexts.net/github/LibreTextsMain/Miscellaneous/Molecules/GLmol/js/Three49custom.js"></script>
    <script type="text/javascript"
            src="https://cdn.libretexts.net/github/LibreTextsMain/Miscellaneous/Molecules/GLmol/js/GLmol.js"></script>
    <script type="text/javascript"
            src="https://cdn.libretexts.net/github/LibreTextsMain/Miscellaneous/Molecules/JSmol/JSmol.full.nojq.js"></script>
    <script type="text/javascript"
            src="https://cdn.libretexts.net/github/LibreTextsMain/Miscellaneous/Molecules/3Dmol/3Dmol-nojquery.js"></script>
<?php }
if (isset($extras['MathJax'])  && $extras['MathJax']) {
    ?>
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
    <script type="text/javascript" async="true"
            src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.3/MathJax.js?config=TeX-AMS_HTML"></script>
    <script type="text/javascript">
        front = document.getElementById('titleHolder').innerText;
        front = front.match(/^.*?:/);
        if (front) {
            front = front[0];
            front = front.split(":")[0];
            if (front.includes(".")) {
                front = front.split(".");
                front = front.map((int) => int.includes("0") ? parseInt(int, 10) : int).join(".");
            }
            front += ".";
        } else {
            front = "";
        }
        console.log(front);
        front = front.replace(/_/g, " ");
        MathJaxConfig = {
            TeX: {
                equationNumbers: {
                    autoNumber: "all",
                    formatNumber: function (n) {
                        return front + n;
                    }
                },
                macros: {
                    PageIndex: ["{" + front + " #1}", 1],
                    test: ["{" + front + " #1}", 1]
                },
                Macros: {
                    PageIndex: ["{" + front + " #1}", 1],
                    test: ["{" + front + " #1}", 1]
                },
                SVG: {
                    linebreaks: {automatic: true}
                }
            }
        };

        //code for Mathjax v3l
        /* MathJax = {
        loader: {
        load: ['[tex]/tagFormat']
        },
        tex: {
        packages: { "[+]": ["tagFormat"] },
        macros: {
        PageIndex: ["{" + front + " #1}", 1]
        },
        tags: "all",
        tagFormat: {
        number: function (n) {
        return front + n;
        }
        }
        }
        }; */

        MathJax.Hub.Config(MathJaxConfig);
        MathJax.Hub.Register.StartupHook("End", () => {
            if (activateBeeLine) activateBeeLine()
        });
    </script>

<?php
}
