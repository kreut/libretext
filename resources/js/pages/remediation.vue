<template>
  <div>
    <div id="navigation">
      <div id="leftside">
        <div id="details">
          <div id="back"><img src="assets/arrow.svg"></div>
          <div id="names">
            <p id="title">Your automation pipeline</p>
            <p id="subtitle">Marketing automation</p>
          </div>
        </div>
      </div>
      <div id="centerswitch">
        <div id="leftswitch">Diagram view</div>
        <div id="rightswitch">Code editor</div>
      </div>
      <div id="buttonsright">
        <div id="discard">Discard</div>
        <div id="publish">Publish to site</div>
      </div>
    </div>
    <div id="leftcard">
      <div id="closecard">
        <img src="assets/closeleft.svg">
      </div>
      <p id="header">Blocks</p>
      <div id="search">
        <img src="assets/search.svg">
        <input type="text" placeholder="Search blocks">
      </div>
      <div id="subnav">
        <div id="triggers" class="navactive side">Triggers</div>
        <div id="actions" class="navdisabled side">Actions</div>
        <div id="loggers" class="navdisabled side">Loggers</div>
      </div>
      <div id="blocklist">
        <div class="blockelem create-flowy noselect">
          <input type="hidden" name='blockelemtype' class="blockelemtype" value="1">
          <div class="grabme">
            <img src="assets/grabme.svg">
          </div>
          <div class="blockin">
            <div class="blockico">
              <span></span>
              <img src="assets/eye.svg">
            </div>
            <div class="blocktext">
              <p class="blocktitle">New visitor</p>
              <p class="blockdesc">Triggers when somebody visits a specified page</p>
            </div>
          </div>
        </div>
        <div class="blockelem create-flowy noselect">
          <input type="hidden" name='blockelemtype' class="blockelemtype" value="2">
          <div class="grabme">
            <img src="assets/grabme.svg">
          </div>
          <div class="blockin">
            <div class="blockico">
              <span></span>
              <img src="assets/action.svg">
            </div>
            <div class="blocktext">
              <p class="blocktitle">Action is performed</p>
              <p class="blockdesc">Triggers when somebody performs a specified action</p>
            </div>
          </div>
        </div>
        <div class="blockelem create-flowy noselect">
          <input type="hidden" name='blockelemtype' class="blockelemtype" value="3">
          <div class="grabme">
            <img src="assets/grabme.svg">
          </div>
          <div class="blockin">
            <div class="blockico">
              <span></span>
              <img src="assets/time.svg">
            </div>
            <div class="blocktext">
              <p class="blocktitle">Time has passed</p>
              <p class="blockdesc">Triggers after a specified amount of time</p>
            </div>
          </div>
        </div>
        <div class="blockelem create-flowy noselect">
          <input type="hidden" name='blockelemtype' class="blockelemtype" value="4">
          <div class="grabme">
            <img src="assets/grabme.svg">
          </div>
          <div class="blockin">
            <div class="blockico">
              <span></span>
              <img src="assets/error.svg">
            </div>
            <div class="blocktext">
              <p class="blocktitle">Error prompt</p>
              <p class="blockdesc">Triggers when a specified error happens</p>
            </div>
          </div>
        </div>
      </div>
      <div id="footer">
        <a href="https://github.com/alyssaxuu/flowy/" target="_blank">GitHub</a>
        <span>&#183;</span>
        <a href="https://twitter.com/alyssaxuu/status/1199724989353730048" target="_blank">Twitter</a>
        <span>&#183;</span>
        <a href="https://alyssax.com" target="_blank"><p>Made with</p><img src="assets/heart.svg"><p>by</p> Alyssa X</a>
      </div>
    </div>
    <div id="propwrap">
      <div id="properties">
        <div id="close">
          <img src="assets/close.svg">
        </div>
        <p id="header2">Properties</p>
        <div id="propswitch">
          <div id="dataprop">Data</div>
          <div id="alertprop">Alerts</div>
          <div id="logsprop">Logs</div>
        </div>
        <div id="proplist">
          <p class="inputlabel">Select database</p>
          <div class="dropme">Database 1 <img src="assets/dropdown.svg"></div>
          <p class="inputlabel">Check properties</p>
          <div class="dropme">All<img src="assets/dropdown.svg"></div>
          <div class="checkus"><img src="assets/checkon.svg"><p>Log on successful performance</p></div>
          <div class="checkus"><img src="assets/checkoff.svg"><p>Give priority to this block</p></div>
        </div>
        <div id="divisionthing"></div>
        <div id="removeblock">Delete blocks</div>
      </div>
    </div>
    <div id="canvas">
    </div>
  </div>
</template>

<script>


  export default {

    metaInfo() {
      return {title: this.$t('home')}
    },

    data: () => ({
      title: window.config.appName
    }),

  mounted() {
    var rightcard = false;
    var tempblock;
    var tempblock2;
    document.getElementById("blocklist").innerHTML = '<div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="1"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/eye.svg"></div><div class="blocktext">                        <p class="blocktitle">New visitor</p><p class="blockdesc">Triggers when somebody visits a specified page</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="2"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/action.svg"></div><div class="blocktext">                        <p class="blocktitle">Action is performed</p><p class="blockdesc">Triggers when somebody performs a specified action</p></div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="3"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/time.svg"></div><div class="blocktext">                        <p class="blocktitle">Time has passed</p><p class="blockdesc">Triggers after a specified amount of time</p>          </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="4"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/error.svg"></div><div class="blocktext">                        <p class="blocktitle">Error prompt</p><p class="blockdesc">Triggers when a specified error happens</p>              </div></div></div>';
    flowy(document.getElementById("canvas"), drag, release, snapping);
    function addEventListenerMulti(type, listener, capture, selector) {
      var nodes = document.querySelectorAll(selector);
      for (var i = 0; i < nodes.length; i++) {
        nodes[i].addEventListener(type, listener, capture);
      }
    }
    function snapping(drag, first) {
      var grab = drag.querySelector(".grabme");
      grab.parentNode.removeChild(grab);
      var blockin = drag.querySelector(".blockin");
      blockin.parentNode.removeChild(blockin);
      if (drag.querySelector(".blockelemtype").value == "1") {
        drag.innerHTML += "<div class='blockyleft'><img src='assets/eyeblue.svg'><p class='blockyname'>New visitor</p></div><div class='blockyright'><img src='assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>When a <span>new visitor</span> goes to <span>Site 1</span></div>";
      } else if (drag.querySelector(".blockelemtype").value == "2") {
        drag.innerHTML += "<div class='blockyleft'><img src='assets/actionblue.svg'><p class='blockyname'>Action is performed</p></div><div class='blockyright'><img src='assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>When <span>Action 1</span> is performed</div>";
      } else if (drag.querySelector(".blockelemtype").value == "3") {
        drag.innerHTML += "<div class='blockyleft'><img src='assets/timeblue.svg'><p class='blockyname'>Time has passed</p></div><div class='blockyright'><img src='assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>When <span>10 seconds</span> have passed</div>";
      } else if (drag.querySelector(".blockelemtype").value == "4") {
        drag.innerHTML += "<div class='blockyleft'><img src='assets/errorblue.svg'><p class='blockyname'>Error prompt</p></div><div class='blockyright'><img src='assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>When <span>Error 1</span> is triggered</div>";
      } else if (drag.querySelector(".blockelemtype").value == "5") {
        drag.innerHTML += "<div class='blockyleft'><img src='assets/databaseorange.svg'><p class='blockyname'>New database entry</p></div><div class='blockyright'><img src='assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>Add <span>Data object</span> to <span>Database 1</span></div>";
      } else if (drag.querySelector(".blockelemtype").value == "6") {
        drag.innerHTML += "<div class='blockyleft'><img src='assets/databaseorange.svg'><p class='blockyname'>Update database</p></div><div class='blockyright'><img src='assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>Update <span>Database 1</span></div>";
      } else if (drag.querySelector(".blockelemtype").value == "7") {
        drag.innerHTML += "<div class='blockyleft'><img src='assets/actionorange.svg'><p class='blockyname'>Perform an action</p></div><div class='blockyright'><img src='assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>Perform <span>Action 1</span></div>";
      } else if (drag.querySelector(".blockelemtype").value == "8") {
        drag.innerHTML += "<div class='blockyleft'><img src='assets/twitterorange.svg'><p class='blockyname'>Make a tweet</p></div><div class='blockyright'><img src='assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>Tweet <span>Query 1</span> with the account <span>@alyssaxuu</span></div>";
      } else if (drag.querySelector(".blockelemtype").value == "9") {
        drag.innerHTML += "<div class='blockyleft'><img src='assets/logred.svg'><p class='blockyname'>Add new log entry</p></div><div class='blockyright'><img src='assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>Add new <span>success</span> log entry</div>";
      } else if (drag.querySelector(".blockelemtype").value == "10") {
        drag.innerHTML += "<div class='blockyleft'><img src='assets/logred.svg'><p class='blockyname'>Update logs</p></div><div class='blockyright'><img src='assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>Edit <span>Log Entry 1</span></div>";
      } else if (drag.querySelector(".blockelemtype").value == "11") {
        drag.innerHTML += "<div class='blockyleft'><img src='assets/errorred.svg'><p class='blockyname'>Prompt an error</p></div><div class='blockyright'><img src='assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>Trigger <span>Error 1</span></div>";
      }
      return true;
    }
    function drag(block) {
      block.classList.add("blockdisabled");
      tempblock2 = block;
    }
    function release() {
      tempblock2.classList.remove("blockdisabled");
    }
    var disabledClick = function(){
      document.querySelector(".navactive").classList.add("navdisabled");
      document.querySelector(".navactive").classList.remove("navactive");
      this.classList.add("navactive");
      this.classList.remove("navdisabled");
      if (this.getAttribute("id") == "triggers") {
        document.getElementById("blocklist").innerHTML = '<div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="1"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/eye.svg"></div><div class="blocktext">                        <p class="blocktitle">New visitor</p><p class="blockdesc">Triggers when somebody visits a specified page</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="2"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/action.svg"></div><div class="blocktext">                        <p class="blocktitle">Action is performed</p><p class="blockdesc">Triggers when somebody performs a specified action</p></div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="3"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/time.svg"></div><div class="blocktext">                        <p class="blocktitle">Time has passed</p><p class="blockdesc">Triggers after a specified amount of time</p>          </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="4"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/error.svg"></div><div class="blocktext">                        <p class="blocktitle">Error prompt</p><p class="blockdesc">Triggers when a specified error happens</p>              </div></div></div>';
      } else if (this.getAttribute("id") == "actions") {
        document.getElementById("blocklist").innerHTML = '<div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="5"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/database.svg"></div><div class="blocktext">                        <p class="blocktitle">New database entry</p><p class="blockdesc">Adds a new entry to a specified database</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="6"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/database.svg"></div><div class="blocktext">                        <p class="blocktitle">Update database</p><p class="blockdesc">Edits and deletes database entries and properties</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="7"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/action.svg"></div><div class="blocktext">                        <p class="blocktitle">Perform an action</p><p class="blockdesc">Performs or edits a specified action</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="8"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/twitter.svg"></div><div class="blocktext">                        <p class="blocktitle">Make a tweet</p><p class="blockdesc">Makes a tweet with a specified query</p>        </div></div></div>';
      } else if (this.getAttribute("id") == "loggers") {
        document.getElementById("blocklist").innerHTML = '<div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="9"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/log.svg"></div><div class="blocktext">                        <p class="blocktitle">Add new log entry</p><p class="blockdesc">Adds a new log entry to this project</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="10"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/log.svg"></div><div class="blocktext">                        <p class="blocktitle">Update logs</p><p class="blockdesc">Edits and deletes log entries in this project</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="11"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/error.svg"></div><div class="blocktext">                        <p class="blocktitle">Prompt an error</p><p class="blockdesc">Triggers a specified error</p>        </div></div></div>';
      }
    }
    addEventListenerMulti("click", disabledClick, false, ".side");
    document.getElementById("close").addEventListener("click", function(){
      if (rightcard) {
        rightcard = false;
        document.getElementById("properties").classList.remove("expanded");
        setTimeout(function(){
          document.getElementById("propwrap").classList.remove("itson");
        }, 300);
        tempblock.classList.remove("selectedblock");
      }
    });

    document.getElementById("removeblock").addEventListener("click", function(){
      flowy.deleteBlocks();
    });
    var aclick = false;
    var noinfo = false;
    var beginTouch = function (event) {
      aclick = true;
      noinfo = false;
      if (event.target.closest(".create-flowy")) {
        noinfo = true;
      }
    }
    var checkTouch = function (event) {
      aclick = false;
    }
    var doneTouch = function (event) {
      if (event.type === "mouseup" && aclick && !noinfo) {
        if (!rightcard && event.target.closest(".block") && !event.target.closest(".block").classList.contains("dragging")) {
          console.log(event.target.closest(".block").classList.contains("dragging"));
          tempblock = event.target.closest(".block");
          rightcard = true;
          document.getElementById("properties").classList.add("expanded");
          document.getElementById("propwrap").classList.add("itson");
          tempblock.classList.add("selectedblock");
        }
      }
    }
    addEventListener("mousedown", beginTouch, false);
    addEventListener("mousemove", checkTouch, false);
    addEventListener("mouseup", doneTouch, false);
    addEventListenerMulti("touchstart", beginTouch, false, ".block");

    }
  }
</script>
<style>
@import "https://cdn.jsdelivr.net/gh/alyssaxuu/flowy/flowy.min.css";
</style>
