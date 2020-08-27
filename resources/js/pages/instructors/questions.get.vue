<template>
  <div v-if="showPage">
    <PageTitle title="Add Questions"></PageTitle>
    <p>Use the search box you can find questions by Tag, Difficulty Level, Author, Textbook, as well as Chapter, Section, and Topic names.
    Adding multiple search parameters will result in a search result which matches all of the conditions.</p>
    <div class="col-5 p-0"><vue-bootstrap-typeahead
      v-model="query"
      :data="tags"
      placeholder="Enter a tag"
      ref="queryTypeahead"
    />
   </div>
    <div class="mt-3 d-flex flex-row">
      <b-button variant="primary" v-on:click="addTag()" class="mr-2">Add Tag</b-button>
      <b-button variant="success" v-on:click="getQuestionsByTags()" class="mr-2">
        <b-spinner small type="grow" v-if="gettingQuestions"></b-spinner>
        Get Questions
      </b-button>
      <b-button variant="secondary" v-on:click="getStudentView(assignmentId)">View as Student</b-button>
    </div>
    <hr>
    <div>
      <h5>Chosen Tags:</h5>
      <div v-if="chosenTags.length>0">
        <ol>
          <li v-for="chosenTag in chosenTags" :key="chosenTag">
            <span v-on:click="removeTag(chosenTag)">{{ chosenTag }}<b-icon icon="trash"
                                                                           variant="danger"></b-icon></span>
          </li>
        </ol>
      </div>
      <div v-else>
        <span class="text-danger">No tags have been chosen.</span>
      </div>
    </div>
    <div class="overflow-auto" v-if="questions.length>0">
      <b-pagination
        v-model="currentPage"
        :total-rows="questions.length"
        :per-page="perPage"
        align="center"
        first-number
        last-number
      ></b-pagination>
    </div>
    <div v-if="showQuestions">
      <div class="d-flex col-4 justify-content-between">
      <div v-if="questions[currentPage-1].inAssignment" class="mt-1 mb-2"
           v-on:click="removeQuestion(questions[currentPage-1])">
        <b-button variant="danger">Remove Question</b-button>
      </div>
      <div v-else class="mt-1 mb-2" v-on:click="addQuestion(questions[currentPage-1])">
        <b-button variant="primary">Add Question</b-button>
      </div>
      <div class="mt-1 mb-2" v-on:click="$router.push(`/instructors/remediations/${questions[currentPage-1].id}`)">
        <b-button variant="info">Create Learning Tree</b-button>
      </div>
     </div>
      <div v-if="questions[currentPage-1].inAssignment">
        <toggle-button
                       @change="toggleQuestionFiles(questions[currentPage-1])"
                       :width="250"
                       :value="questions[currentPage-1].questionFiles"
                       :sync="true"
                       :font-size="14"
                       :margin="4"
                       :color="{checked: '#007BFF', unchecked: '#75C791'}"
                       :labels="{checked: 'Disable Question File Upload', unchecked: 'Enable Question File Upload'}"/>
      </div>
        <iframe v-bind:id="questions[currentPage-1].questionIframeId"
                allowtransparency="true" frameborder="0"
                v-bind:src="questions[currentPage-1].src"
                v-on:load="showIframe(questions[currentPage-1].questionIframeId)" v-show="iframeLoaded"
                style="width: 1px;min-width: 100%;"
        >

      </iframe>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import {getQuestionSrc} from '~/helpers/Questions'
import {ToggleButton} from 'vue-js-toggle-button'

export default {
  components: {
    VueBootstrapTypeahead,
    ToggleButton
  },
  middleware: 'auth',
  data: () => ({
    iframeLoaded: false,
    perPage: 1,
    currentPage: 1,
    query: '',
    tags: [],
    questions: [],
    chosenTags: [],
    question: {},
    showQuestions: false,
    gettingQuestions: false,
    showPage: false
  }),
  created() {
    this.getQuestionSrc = getQuestionSrc
  },
  mounted() {
    this.assignmentId = this.$route.params.assignmentId
    this.tags = this.getTags();
  },
  methods: {
    async toggleQuestionFiles(question){
      console.log(question)
      this.questions[this.currentPage - 1].questionFiles = !question.questionFiles
  try {
    const {data} = await axios.patch(`/api/assignments/${this.assignmentId}/questions/${question.id}/toggle-question-files`,
    {question_files : this.questions[this.currentPage - 1].questionFiles })
    this.$noty[data.type](data.message)
  } catch (error) {
    this.questions[this.currentPage - 1].questionFiles = !question.questionFiles
    console.log(error)
    this.$noty.error('We could not toggle the question files option.  Please try again or contact us for assistance.')
  }
},
    showIframe(id) {
      this.iframeLoaded = true
      iFrameResize({log: true}, `#${id}`)
    },
    removeTag(chosenTag) {
      this.chosenTags = _.without(this.chosenTags, chosenTag);
      console.log(this.chosenTags)
    },
    addTag() {

      if ((this.query !== '') && this.tags.includes(this.query) && !this.chosenTags.includes(this.query)) {
        this.chosenTags.push(this.query)
      }
      this.$refs.queryTypeahead.inputValue = this.query = '' //https://github.com/alexurquhart/vue-bootstrap-typeahead/issues/22
    },
    async getTags() {
      try {
        const {data} = await axios.get(`/api/tags`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        } else {
          this.tags = data.tags
          this.showPage = true
        }
      } catch (error) {
        this.$noty.error(error.message)
      }

    },
    async addQuestion(question) {
      try {
        this.questions[this.currentPage - 1].questionFiles = false
        const {data} = await axios.post(`/api/assignments/${this.assignmentId}/questions/${question.id}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.questions[this.currentPage - 1].inAssignment = true
        }

      } catch (error) {
        console.log(error)
        this.$noty.error('We could not add the question to the assignment.  Please try again or contact us for assistance.')
      }

    },
    async removeQuestion(question) {
      try {
        const {data} = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${question.id}`)
        if (data.type === 'success') {
          this.$noty.info(data.message)
          question.inAssignment = false
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }

    },
    async getQuestionsByTags() {
      this.questions = []
      this.showQuestions = false
      this.gettingQuestions = true
      this.addTag() //in case they didn't click
      try {
        if (this.chosenTags.length === 0) {
          this.$noty.error('Please choose at least one tag.')
          this.gettingQuestions = false
          return false
        }
        const {data} = await axios.post(`/api/questions/getQuestionsByTags`, {'tags': this.chosenTags})
      let questionsByTags = data

        if (questionsByTags.type === 'success' && questionsByTags.questions.length>0) {
          //get whether in the assignment and get the url
          const {data} = await axios.get(`/api/assignments/${this.assignmentId}/questions/question-info`)
          console.log(data)
          let questionInfo = data
console.log(questionInfo)
          if (questionInfo.type === 'success') {

            for (let i = 0; i < questionsByTags.questions.length; i++) {
              console.log(questionsByTags.questions)
              questionsByTags.questions[i].inAssignment = questionInfo.question_ids.includes(questionsByTags.questions[i].id)
              questionsByTags.questions[i].questionFiles = questionInfo.question_files.includes(questionsByTags.questions[i].id)

              questionsByTags.questions[i].src = this.getQuestionSrc(questionsByTags.questions[i])
              questionsByTags.questions[i].questionIframeId = `getQuestionIframe-${i}`
            }
            this.questions = questionsByTags.questions
            console.log(this.questions)
            this.showQuestions = true
          } else {
            this.$noty.error(questionIds.message)
          }
        } else {

          this.$noty.error(questionsByTags.message)
        }

      } catch (error) {
        this.$noty.error(error.message)
      }
      this.gettingQuestions = false
    },
    getStudentView(assignmentId) {
      this.$router.push(`/assignments/${assignmentId}/questions/view`)
    }
  },
  metaInfo() {
    return {title: this.$t('home')}
  }
}
</script>
