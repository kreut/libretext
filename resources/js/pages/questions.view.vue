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
        <div v-if="this.currentLearningTreeLevel.length>0">
          <b-alert show>
            <h5>Need some help?  One of the Student Learning Objectives below might be useful.</h5>
            <template v-for="remediationObject in this.currentLearningTreeLevel">
              <li v-for="(value, name) in remediationObject"
                  v-on:click="explore(remediationObject.library, remediationObject.pageId)"
                  v-show="name === 'text'"
                  v-html="value"></li>
            </template>




          </b-alert>
        </div>
        <iframe allowtransparency="true" frameborder="0"
                v-bind:src="questions[currentPage-1].src"
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
import {getSrc} from '~/helpers/Questions'

export default {

  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({
    level: 0,
    learningTree: [],
    currentLearningTreeLevel: [],
    currentLibrariesAndPageIds: [],
    parentId: 0,
    perPage: 1,
    currentPage: 1,
    questions: [],
    initializing: true, //use to show a blank screen until all is loaded
    title: '',
    assignmentId: ''
  }),
  created() {
    this.getSrc = getSrc
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
    async resetLearningTree(learningTree) {
      console.log(learningTree)
      this.currentLearningTreeLevel = []
      this.currentLibrariesAndPageIds = []
      if (learningTree) {
        this.level = 0
        this.parentId = 0
        this.learningTree = learningTree
        //loop through and get all with parent = -1

        //loop through each with parent having this level
        let page_id
        let library
        for (let i = 0; i < this.learningTree.length; i++) {
          let remediation = this.learningTree[i]

          if (remediation.parent === 0) {

            //get the library and page ids
            //go to the server and return with the student learning objectives
            // "parent": 0, "data": [ { "name": "blockelemtype", "value": "2" },{ "name": "page_id", "value": "21691" }, { "name": "library", "value": "chem" }, { "name": "blockid", "value": "1" } ], "at}

            page_id = library = null
            for (let j = 0; j < remediation.data.length; j++) {
              switch (remediation.data[j].name) {
                case('page_id'):
                  page_id = remediation.data[j].value
                  break
                case ('library'):
                  library = remediation.data[j].value
                  break
              }
            }
            if (page_id && library) {
              this.currentLibrariesAndPageIds.push({'library': library, 'page_id': page_id})
            }

          }
        }
        //get the learning objectives
        for (let i = 0; i <  this.currentLibrariesAndPageIds.length; i++) {
          let library =this.currentLibrariesAndPageIds[i]['library']
        let pageId = this.currentLibrariesAndPageIds[i]['page_id']
          const {data} = await axios.get(`/api/student-learning-objectives/${library}/${pageId}`)
          let d = document.createElement('div');
          d.innerHTML = data
          for (const li of d.querySelector("ul").querySelectorAll('li')) {
            let remediation = {'library' : library,
            'pageId': pageId,
            'studentLearningObjective': li.innerText,
            'text': `${li.innerText} Explore!`}
           this.currentLearningTreeLevel.push(remediation)
          }
        }
        console.log('done')


      }
    },
    explore(library, pageId){
      console.log(library, pageId)
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
          this.questions[i].src = this.getSrc(this.questions[i])
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
