<template>
  <div>

    <div v-if="!initializing">
      <PageTitle v-bind:title="this.title"></PageTitle>
      <div v-if="questions.length">
        <div class="d-flex justify-content-between">
          <div class="mt-1 mb-2" v-on:click="getQuestionsForAssignment()" v-if="user.role !== 3">
            <b-button variant="success">Get Questions</b-button>
          </div>
          <div class="overflow-auto">
            <b-pagination
              v-model="currentPage"
              :total-rows="questions.length"
              :per-page="perPage"
              first-number
              last-number
              v-on:input="resetLearningTree(questions[currentPage - 1].learning_tree)"
            ></b-pagination>
          </div>
          <div class="mt-1 mb-2" v-on:click="removeQuestion(currentPage)" v-if="user.role !== 3">
            <b-button variant="danger">Remove Question</b-button>
          </div>
        </div>
        <div v-if="this.learningTreeAsList.length>0">
          <b-alert show>
            <h5>Need some help? One of the Student Learning Objectives below might be useful.</h5>
            <template v-for="remediationObject in this.learningTreeAsList">
              <li v-for="(value, name) in remediationObject"
                  v-if="(remediationObject.show) && (name === 'studentLearningObjective')">
                <b-button variant="link" v-html="value" v-on:click="explore(remediationObject.library, remediationObject.pageId)">
                </b-button>
                <b-button variant="info" size="sm" v-if="remediationObject.children" v-on:click="more(remediationObject)">
                  More
                </b-button>

              </li>
            </template>
          </b-alert>
        </div>

        <iframe allowtransparency="true" frameborder="0"
                v-bind:src="remediationSrc"
                style="overflow: auto; height: 1274px;"
                v-if="!showQuestion"
                width="100%">
        </iframe>
        <iframe allowtransparency="true" frameborder="0"
                v-bind:src="questions[currentPage-1].src"
                v-if="showQuestion"
                style="overflow: auto; height: 1274px;"
                width="100%">
        </iframe>

      </div>
      <div v-else>
        <div v-if="questions !== ['init']">
          <div class="mt-1 mb-2" v-on:click="getQuestionsForAssignment()" v-if="user.role !== 3">
            <b-button variant="success">Get More Questions</b-button>
          </div>
          <div class="mt-4">
            <b-alert :show="true" variant="warning"><a href="#" class="alert-link">This assignment currently has no
              questions.
            </a></b-alert>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<script>
import axios from 'axios'
import {mapGetters} from "vuex"
import {getQuestionSrc} from '~/helpers/Questions'

export default {

  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({
    showQuestion: true,
    remediationSrc: '',
    learningTree: [],
    currentLearningTreeLevel: [],
    learningTreeAsList: [],
    learningTreeAsList_1: [],
    perPage: 1,
    currentPage: 1,
    questions: [],
    initializing: true, //use to show a blank screen until all is loaded
    title: '',
    assignmentId: ''
  }),
  created() {
    this.getQuestionSrc = getQuestionSrc
  },
  mounted() {
    this.assignmentId = this.$route.params.assignmentId
    this.getTitle(this.assignmentId)
    this.getSelectedQuestions(this.assignmentId)
    let vm = this
    if (this.user.role === 3) {
      let receiveMessage = async function (event) {
        if (event.data.action !== 'hello') {
          let submission_data = {
            'submission': event.data,
            'assignment_id': vm.assignmentId,
            'question_id': vm.questions[vm.currentPage - 1].id
          }
          console.log(submission_data)

          //if incorrect, show the learning tree stuff...
          let {data} = await axios.post('/api/submissions', submission_data)
          console.log(data)
          if (data.message) {
            //Will add this later when we've worked out what it means to submit...vm.$noty[data.type](data.message)
          }

        } else {
          console.log('Hello Event')
        }
      }
      window.addEventListener("message", receiveMessage, false)
    }
  },
  methods: {
    more(remediationObject) {

      for (let i =0; i < this.learningTreeAsList.length; i++){
        console.log(this.learningTreeAsList[i].id)
        this.learningTreeAsList[i].show = remediationObject.children.includes(this.learningTreeAsList[i].id)
      }
    },
    async resetLearningTree(learningTree) {
      console.log(learningTree)
      this.learningTreeAsList = []
      if (learningTree) {
        //loop through and get all with parent = -1

        //loop through each with parent having this level
        let pageId
        let library
        console.log('length ' + learningTree.length)
        for (let i = 0; i < learningTree.length; i++) {
          let remediation = learningTree[i]
          //get the library and page ids
          //go to the server and return with the student learning objectives
          // "parent": 0, "data": [ { "name": "blockelemtype", "value": "2" },{ "name": "page_id", "value": "21691" }, { "name": "library", "value": "chem" }, { "name": "blockid", "value": "1" } ], "at}

          pageId = library = null
          let parent = remediation.parent
          let id = remediation.id
          for (let j = 0; j < remediation.data.length; j++) {
            switch (remediation.data[j].name) {
              case('page_id'):
                pageId = remediation.data[j].value
                break
              case ('library'):
                library = remediation.data[j].value
                break
              case('id'):
                id = remediation.data[j].value
            }
          }
          if (pageId && library) {
            const {data} = await axios.get(`/api/student-learning-objectives/${library}/${pageId}`)
            let d = document.createElement('div');
            d.innerHTML = data
            let text = ''
            for (const li of d.querySelector("ul").querySelectorAll('li')) {
              text += li.innerText + '<br>'
            }
            let remediation = {
              'library': library,
              'pageId': pageId,
              'studentLearningObjective': text,
              'parent': parent,
              'id': id,
              'show': (parent === 0)
            }
            this.learningTreeAsList.push(remediation)
          }
          for (let i = 0; i < this.learningTreeAsList.length; i++) {
            this.learningTreeAsList[i]['children'] = []

            for (let j = 0; j < this.learningTreeAsList.length; j++) {

              if (i !== j && (this.learningTreeAsList[j]['parent'] === this.learningTreeAsList[i]['id'])) {
                this.learningTreeAsList[i]['children'].push(this.learningTreeAsList[j]['id'])
              }
            }

          }
        }


        console.log('done')
        console.log(this.learningTreeAsList)
        this.learningTreeAsList_1 = this.learningTreeAsList


      }
    },
    explore(library, pageId) {
      this.showQuestion = false
      this.remediationSrc = `https://${library}.libretexts.org/@go/page/${pageId}`
    },
    async getTitle(assignmentId) {
      try {
        const {data} = await axios.get(`/api/assignments/${assignmentId}`)
        this.title = `${data.name} Assignment Questions`
      } catch (error) {
        this.title = "View Questions"
      }
    },
    async getSelectedQuestions(assignmentId) {
      try {
        const {data} = await axios.get(`/api/assignments/${assignmentId}/questions/view`)
        console.log(JSON.parse(JSON.stringify(data)))


        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questions = data.questions
        for (let i = 0; i < this.questions.length; i++) {
          this.questions[i].src = this.getQuestionSrc(this.questions[i])
        }

        this.initializing = false
      } catch (error) {
        alert(error)
        this.$noty.error('We could not retrieve the questions for this assignment.  Please try again or contact us for assistance.')
      }
    },
    getQuestionsForAssignment() {
      this.$router.push(`/assignments/${this.assignmentId}/questions/get`)
    },
    async removeQuestion(currentPage) {
      try {
        axios.delete(`/api/assignments/${this.assignmentId}/questions/${this.questions[currentPage - 1].id}`)
        this.$noty.info('The question has been removed from the assignment.')
        this.questions.splice(currentPage - 1, 1);
      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }
    }
  },
  metaInfo() {
    return {title: this.$t('home')}
  }
}
</script>
