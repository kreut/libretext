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
}
else {
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
      linebreaks: { automatic: true }
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
MathJax.Hub.Register.StartupHook("End", () => { if (activateBeeLine) activateBeeLine() });
