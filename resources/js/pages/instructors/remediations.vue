<template>
  <div>
    <div id="leftcard">
        <b-button variant="success" v-on:click="saveLearningTree">Save Learning Tree</b-button>
      <div id="search">
        <div class="mb-2 mr-2">
          <b-form-select v-model="library" :options="libraryOptions" class="mt-3"></b-form-select>
        </div>
        <div class="d-flex flex-row">
          <b-form-input v-model="pageId" style="width: 100px" placeholder="Page Id"></b-form-input>
          <b-button class="ml-2" variant="primary" id="add" v-on:click="addRemediation">Add Remediation</b-button>
        </div>

      </div>
      <div id="blocklist">
      </div>
    </div>

    <div id="canvas">
    </div>

  </div>
</template>

<script>


import {flowy} from '~/helpers/Flowy'

import axios from "axios";

export default {

  metaInfo() {
    return {title: this.$t('home')}
  },
  data: () => ({
    panelHidden: false,
    studentLearningObjectives: '',
    title: window.config.appName,
    pageId: '',
    chosenId: '',
    library: null,
    libraryColors: {
      'chem': 'rgb(0, 191, 255)',
      'biz': 'rgb(32, 117, 55)'
    },
    libraryOptions: [
      {value: null, text: 'Please select  the library'},
      {value: 'bio', text: 'Biology'},
      {value: 'biz', text: 'Business'},
      {value: 'chem', text: 'Chemistry'},
      {value: 'eng', text: 'Engineering'},
      {value: 'espanol', text: 'Español'},
      {value: 'geo', text: 'Geology'},
      {value: 'human', text: 'Humanities'},
      {value: 'math', text: 'Mathematics'},
      {value: 'med', text: 'Medicine'},
      {value: 'phys', text: 'Physics'},
      {value: 'socialsci', text: 'Social Science'},
      {value: 'stats', text: 'Statistics'},
      {value: 'workforce', text: 'Workforce'},
    ]
  }),

  mounted() {

    this.questionId = this.$route.params.questionId

    let tempblock;
    let tempblock2;
    console.log(document.getElementById("canvas"))

    flowy(document.getElementById("canvas"), drag, release, snapping, rearranging, 40, 50);

    function addEventListenerMulti(type, listener, capture, selector) {
      let nodes = document.querySelectorAll(selector);
      for (let i = 0; i < nodes.length; i++) {
        nodes[i].addEventListener(type, listener, capture);
      }
    }

    function rearranging(block, parent) {
      // Needed so that I could redefine the y distance in flowy
    }

    function snapping(drag, first) {
      let grab = drag.querySelector(".grabme");
      grab.parentNode.removeChild(grab);

      let blockin = drag.querySelector(".blockin");

      blockin.parentNode.removeChild(blockin);
      let isAssessmentNode = (drag.querySelector(".blockelemtype").value === "1")

      let title = isAssessmentNode ? 'Assessment' : 'Remediation'

      let library = isAssessmentNode ? '' : blockin.querySelector(".library").innerHTML
      let pageId = isAssessmentNode ? '' : blockin.querySelector(".pageId").innerHTML


      let body = isAssessmentNode ? "The original question" :
        `<div>Library: <span class="library" >${library}</span>, Page Id: <span class="pageId" >${pageId}</span><br>
<span class="open-student-learning-objective-modal">Student Learning Objectives</span></div>`
      drag.innerHTML += `<div class='blockyleft'>
<p class='blockyname'><img src="/assets/img/${library[0].toLowerCase()+library.slice(1)}.svg"></span>${title}</p></div>
<div class='blockydiv'></div>
<div class='blockyinfo'>
${body}
</div>`;
      return true;
    }

    function drag(block) {
      block.classList.add("blockdisabled");
      tempblock2 = block;
    }


    function release() {
      if (tempblock2) {
        //if it's reloading a saved learning tree, this won't exist
        tempblock2.classList.remove("blockdisabled");
      }
    }

    let aclick = false;
    let noinfo = false;
    let beginTouch = function (event) {
      aclick = true;
      noinfo = false;

      if (event.target.closest(".create-flowy")) {

        noinfo = true;
      }
    }
    let checkTouch = function (event) {
      aclick = false;
    }


    let vm = this
    let doneTouch = function (event) {

      console.log(event.target.className)
      if (event.target.className === 'open-student-learning-objective-modal') {
        vm.pageId = event.target.parentNode.parentNode.querySelector('.pageId').innerHTML
        vm.library = event.target.parentNode.parentNode.querySelector('.library').innerHTML.toLowerCase()
        console.log(vm.pageId)
        console.log(vm.library)
        vm.openStudentLearningObjectiveModal()
      } else if (event.type === "mouseup" && aclick && !noinfo) {

        if (event.target.closest(".block") && !event.target.closest(".block").classList.contains("dragging")) {
          console.log(event.target.closest(".block").classList.contains("dragging"));
          tempblock = event.target.closest(".block");
          document.getElementById("properties").classList.add("expanded");
          tempblock.classList.add("selectedblock");
        }
      }
    }

    addEventListener("mousedown", beginTouch, false);
    addEventListener("mousemove", checkTouch, false);
    addEventListener("mouseup", doneTouch, false);
    addEventListenerMulti("touchstart", beginTouch, false, ".block");

    this.getLearningTreeByQuestionId(this.questionId)


  },
  methods: {
    async getLearningTreeByQuestionId(questionId) {
      console.log('getting learning tree')
      try {
        const {data} = await axios.get(`/api/learning-trees/${questionId}`)

        flowy.import(JSON.parse(data.learning_tree))

      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async saveLearningTree() {
      try {
        const {data} = await axios.post('/api/learning-trees', {
          'question_id': this.questionId,
          'learning_tree': JSON.stringify(flowy.output())
        })
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async openStudentLearningObjectiveModal() {
      const {data} = await axios.get(`/libreverse/library/${this.library}/page/${this.pageId}/student-learning-objectives`)
      let d = document.createElement('div');
      d.innerHTML = data

      const h = this.$createElement

      const titleVNode = h('div', {domProps: {innerHTML: 'Student Learning Objectives'}})
      let messageVNode
      if (d.querySelector("ul")) {
        messageVNode = h('ul', [])
        for (const li of d.querySelector("ul").querySelectorAll('li')) {
          messageVNode.children.push(h('li', [li.textContent]))
        }
      } else {
        messageVNode = h('p', {class: 'text-danger'}, ['There are no Student Learning Objectives available.'])
      }

      console.log(messageVNode)
      // We must pass the generated VNodes as arrays
      this.$bvModal.msgBoxOk([messageVNode], {
        title: [titleVNode],
        buttonSize: 'sm',
        centered: true, size: 'lg'
      })


      this.$bvModal.show('student-learning-objective-modal')
    },
    validateRemediation() {
      if (!this.library) {
        this.$noty.error('Please choose a library.')
        return false
      }
      if (!(Number.isInteger(parseFloat(this.pageId)) && parseInt(this.pageId) > 0)) {
        this.$noty.error('Your Page Id should be a positive integer.')
        return false
      }
      return true
    },
    addRemediation() {
      if (!this.validateRemediation()) {
        return false
      }

      let blockElems = document.querySelectorAll('div.blockelem.create-flowy.noselect')

      let newBlockElem = `<div class="blockelem create-flowy noselect" style="border: 1px solid ${this.libraryColors[this.library]}">
        <input type="hidden" name='blockelemtype' class="blockelemtype" value="${blockElems.length + 2}">
        <input type="hidden" name='page_id' value="${this.pageId}">
        <input type="hidden" name='library' value="${this.library}">
<div class="grabme">
</div>
      <div class="blockin">
        <div class="blockyleft">
          <p class="blockyname"> <img src="/assets/img/${this.library}.svg" style="${this.libraryColors[this.library]}">Remediation</p>
        </div>
          <div class='blockydiv'>
          </div>
          <div class="blockin-info">
          <span class="blockdesc">Library: <span class="library">${this.library[0].toUpperCase() +
      this.library.slice(1)}</span>,
          Page Id: <span class="pageId">${this.pageId}</span>
          <br>
          <span class="open-student-learning-objective-modal">Student Learning Objectives</span>
        </div>
        </div>
    </div>`
      if (blockElems.length > 0) {
        let lastBlockElem = blockElems[blockElems.length - 1]
        lastBlockElem.insertAdjacentHTML('afterend', newBlockElem);
      } else {
        document.getElementById("blocklist").innerHTML += newBlockElem
      }

    }
  }
}
</script>
<style>
@import '/dist/css/flowy.css';
</style>
