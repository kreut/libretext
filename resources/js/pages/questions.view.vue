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

            <div class="text-center" v-if="!loadedTitles">
              <h5>
                <b-spinner variant="primary" type="grow" label="Spinning"></b-spinner>
                Loading
              </h5>
            </div>
            <div v-else>
              <div class="d-flex justify-content-between mb-2">
                <h5>Need some help? Explore the topics below.</h5>
                <b-button class="float-right" :disabled="showQuestion" variant="primary"
                          v-on:click="viewOriginalQuestion">View Original
                  Question
                </b-button>

              </div>
              <hr>
              <b-container class="bv-example-row">
                <b-row align-h="center">
                  <template v-for="remediationObject in this.learningTreeAsList">
                    <b-col cols="4" v-for="(value, name) in remediationObject" v-bind:key="value.id"
                           v-if="(remediationObject.show) && (name === 'title')">
                      <b-row align-h="center">
                        <b-col cols="4">
                          <div class="h2 mb-0">
                            <b-icon variant="info" v-if="remediationObject.parent > 0"
                                    v-on:click="back(remediationObject)" icon="arrow-up-square-fill">
                            </b-icon>
                          </div>
                        </b-col>
                      </b-row>
                      <div class="border border-info mr-1 p-3 rounded">
                        <b-row align-h="center">
                          <div class="mr-1 ml-2"><strong>{{ remediationObject.title }}</strong></div>
                          <b-button size="sm" class="mr-2" variant="success"
                                    v-on:click="explore(remediationObject.library, remediationObject.pageId)">
                            Go!
                          </b-button>
                        </b-row>
                      </div>
                      <b-container>
                        <b-row align-h="center">
                          <b-col cols="4">
                            <div class="h2 mb-0">
                              <b-icon v-if="remediationObject.children.length"
                                      v-on:click="more(remediationObject)" icon="arrow-down-square-fill"
                                      variant="info">
                              </b-icon>
                            </div>
                          </b-col>
                        </b-row>
                      </b-container>
                    </b-col>
                  </template>
                </b-row>
              </b-container>
            </div>
          </b-alert>
        </div>

        <div v-if="!iframeLoaded" class="text-center">
          <h5>
            <b-spinner variant="primary" type="grow" label="Spinning"></b-spinner>
            Loading...
          </h5>
        </div>
        <iframe v-bind:id="remediationIframeId"
                allowtransparency="true" frameborder="0"
                v-bind:src="remediationSrc"
                style="width: 1px;min-width: 100%;"
                v-if="!showQuestion"
                v-on:load="showIframe(remediationIframeId)" v-show="iframeLoaded"
                >
        </iframe>
        <div  v-if="showQuestion"  v-show="iframeLoaded">
        <iframe v-bind:id="questions[currentPage-1].questionIframeId"
                allowtransparency="true" frameborder="0"
                v-bind:src="questions[currentPage-1].src"
                v-on:load="showIframe(questions[currentPage-1].questionIframeId)"
                style="width: 1px;min-width: 100%;"
                >
        </iframe>
        </div>


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
    remediationIframeId: '',
    iframeLoaded: false,
    loadedTitles: false,
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
    this.getAssignmentName(this.assignmentId)
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
          //console.log(data)
          if (data.message) {
            //Will add this later when we've worked out what it means to submit...vm.$noty[data.type](data.message)
          }

        } else {
         // console.log('Hello Event')
        }
      }
      window.addEventListener("message", receiveMessage, false)
    }
  },
  methods: {
    viewOriginalQuestion(){
      this.showQuestion = true
    },
    showIframe(id) {
      this.iframeLoaded = true
      iFrameResize({log: true}, `#${id}`)
    },
    back(remediationObject) {
      let parentIdToShow = false
      for (let i = 0; i < this.learningTreeAsList.length; i++) {
        if (this.learningTreeAsList[i].id === remediationObject.parent) {
          parentIdToShow = this.learningTreeAsList[i].parent
        }
      }
      for (let i = 0; i < this.learningTreeAsList.length; i++) {
        this.learningTreeAsList[i].show = (this.learningTreeAsList[i].parent === parentIdToShow)
      }

    },
    more(remediationObject) {

      for (let i = 0; i < this.learningTreeAsList.length; i++) {
        //console.log(this.learningTreeAsList[i].id)
        this.learningTreeAsList[i].show = remediationObject.children.includes(this.learningTreeAsList[i].id)
      }
    },
    async resetLearningTree(learningTree) {
      this.showQuestion = true
      this.learningTreeAsList = []
      if (learningTree) {
        //loop through and get all with parent = -1

        //loop through each with parent having this level
        let pageId
        let library
       // console.log('length ' + learningTree.length)
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
            const {data} = await axios.get(`/api/libreverse/library/${library}/page/${pageId}/title`)
            let remediation = {
              'library': library,
              'pageId': pageId,
              'title': data,
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
        this.loadedTitles = true

      }
    },
    explore(library, pageId) {
      this.showQuestion = false
      this.iframeLoaded = false
      this.remediationSrc = `https://${library}.libretexts.org/@go/page/${pageId}`
      this.remediationIframeId = `remediation-${library}-${pageId}`
    },
    async getAssignmentName(assignmentId) {
      try {
        const {data} = await axios.get(`/api/assignments/${assignmentId}`)
        this.title = `${data.name} Assignment Questions`
      } catch (error) {
          this.$noty.error(error.message)
      }
    },
    async getSelectedQuestions(assignmentId) {
      try {
        const {data} = await axios.get(`/api/assignments/${assignmentId}/questions/view`)
       // console.log(JSON.parse(JSON.stringify(data)))


        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questions = data.questions
       // console.log(data.questions)
        for (let i = 0; i < this.questions.length; i++) {
          this.questions[i].src = this.getQuestionSrc(this.questions[i])
          this.questions[i].questionIframeId = `viewQuestionIframe-${this.questions[i].id}`
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
<style scoped>
svg:hover {
  fill: #138496;
}
</style>
