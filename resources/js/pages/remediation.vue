<template>
  <div>

    <div id="leftcard">
      <div class="float-right mr-2">
        <b-button variant="success" v-on:click="saveLearningTree">Save Learning Tree</b-button>
      </div>
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
    <b-modal
      id="modal-learning-objective"
      ref="modal"
      title="Attach Learning Objective"
      size="lg"
      @ok="submitAttachLearningObjective"
      @hidden="resetModals()"
      ok-title="Submit"

    >
      <b-form ref="form" @submit="submitAttachLearningObjective">
        <div class="p-0">
          <vue-bootstrap-typeahead
            v-model="query"
            :data="learningObjectives"
            placeholder="Enter a Learning Objective"
            ref="queryTypeahead"
            @click="form.errors.clear('learning_objective')"
          />
        </div>
      </b-form>
    </b-modal>
  </div>
</template>

<script>


import {flowy} from '~/helpers/Flowy'
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import Form from "vform";
import axios from "axios";

export default {
  components: {
    VueBootstrapTypeahead
  },
  metaInfo() {
    return {title: this.$t('home')}
  },

  data: () => ({
    query: '',
    form: new Form({
      learning_objective: '',
      pageId: '',
      library: '',
    }),
    learningObjectives: '',
    title: window.config.appName,
    pageId: '',
    chosenId: '',
    library: null,
    libraryOptions: [
      {value: null, text: 'Please select  the library'},
      {value: 'query', text: 'Query'},
      {value: 'engineering', text: 'Engineering'},
    ]
  }),

  mounted() {
    this.questionId = this.$route.params.questionId

    this.getLearningObjectives();

    console.log(this.learningObjectives)
    let tempblock;
    let tempblock2;
    console.log(document.getElementById("canvas"))

    flowy(document.getElementById("canvas"), drag, release, snapping);

    function addEventListenerMulti(type, listener, capture, selector) {
      let nodes = document.querySelectorAll(selector);
      for (let i = 0; i < nodes.length; i++) {
        nodes[i].addEventListener(type, listener, capture);
      }
    }

    function snapping(drag, first) {
      let grab = drag.querySelector(".grabme");
      grab.parentNode.removeChild(grab);

      let blockin = drag.querySelector(".blockin");

      blockin.parentNode.removeChild(blockin);
      let isAssessmentNode = (drag.querySelector(".blockelemtype").value === "1")

      let title = isAssessmentNode ? 'Assessment Node' : 'Remediation'

      let library = isAssessmentNode ? '' : blockin.querySelector(".library").innerHTML
      let pageId = isAssessmentNode ? '' : blockin.querySelector(".pageId").innerHTML


      let body = isAssessmentNode ? "The original question" : `<div>Library: <span class="library remediation-info" >${library}</span></div>
      <div>Page Id: <span class="pageId remediation-info" >${pageId}</span></div>
       <div>Learning Objective: <span class="learningObjective remediation-info">Some Learning objective</div>`
      drag.innerHTML += `<div class='blockyleft'>
<p class='blockyname'>${title}</p></div>
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


      if (event.target.className === 'open-learning-objective-modal') {
        console.log(event.target)
        vm.form.pageId = event.target.parentNode.parentNode.querySelector('.pageId').innerHTML
        vm.form.library = event.target.parentNode.parentNode.querySelector('.library').innerHTML.toLowerCase()
        console.log(event.target.parentNode.parentNode.querySelector('.learningObjective'))
        event.target.parentNode.parentNode.querySelector('.learningObjective').setAttribute("id", "chosen");
        vm.openLearningObjectiveModal()
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
    async getLearningObjectives() {
      try {
        const {data} = await axios.get('/api/learning-objectives')
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        } else {
          this.learningObjectives = data.learning_objectives
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },

    resetModals() {
      //handled through the resetAll
    },
    resetAll(modalId) {
      this.form.learning_objective = ''
      this.form.pageId = ''
      this.form.library = ''
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    },
    openLearningObjectiveModal() {
      this.$bvModal.show('modal-learning-objective')
    },
    submitAttachLearningObjective(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      this.attachLearningObjective()
    }
    ,
    async attachLearningObjective() {
      try {
        this.form.learning_objective = this.query
        const {data} = await this.form.post('/api/learning-objectives/attach')
        if (data.type === 'success') {
          this.resetAll('modal-learning-objective')
        }
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    addRemediation() {

      // this.chosenId

      let blockElems = document.querySelectorAll('div.blockelem.create-flowy.noselect')


      let newBlockElem = `<div class="blockelem create-flowy noselect">
        <input type="hidden" name='blockelemtype' class="blockelemtype" value="${blockElems.length + 2}">
        <div class="grabme">
        <img src="/assets/img/grabme.svg">
        </div>
      <div class="blockin">
        <div class="blocktext">
          <p class="blocktitle">Remediation</p>
          <p class="blockdesc">Library: <span class="library">${this.library[0].toUpperCase() +
      this.library.slice(1)}</span>
          <br>
          Page Id: <span class="pageId">${this.pageId}</span>
          <br>
       Learning Objective: <span class="learningObjective remediation-info"><span class="open-learning-objective-modal">Attach</span>
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
<style scoped lang="scss">
@import '/dist/css/flowy.css';
</style>

