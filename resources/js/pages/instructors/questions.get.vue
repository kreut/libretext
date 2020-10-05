<template>
  <div>
    <div v-if="isLoading" class="text-center">
      <h3>
        <b-spinner variant="primary" type="grow" label="Spinning"></b-spinner>
        Loading...
      </h3>
    </div>
    <div v-if="showPage">
      <PageTitle :title="title"></PageTitle>
      <p>Use the search box you can find questions by tag.
        The tag can be a word associated with the question or can be the query library page id. To search
        by page id, please use the tag: id={id}. For example, id=112358.
        Note that adding multiple tags will result in a search result which matches all of the conditions.</p>
      <div class="col-5 p-0">
        <vue-bootstrap-typeahead
          v-model="query"
          :data="tags"
          placeholder="Enter a tag or page id"
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
          v-on:input="changePage(currentPage)"
        ></b-pagination>
      </div>
      <div v-if="showQuestions">
        <div class="d-flex">
          <div v-if="questions[currentPage-1].inAssignment" class="mt-1 mb-2 mr-2"
               v-on:click="removeQuestion(questions[currentPage-1])">
            <b-button variant="danger">Remove Question</b-button>
          </div>
          <div>
            <b-button v-if="!questions[currentPage-1].inAssignment" class="mt-1 mb-2 mr-2"
                      v-on:click="addQuestion(questions[currentPage-1])" variant="primary">Add Question
            </b-button>

            <b-button v-if="questions[currentPage-1].inAssignment" class="mt-1 mb-2"
                      v-on:click="$router.push(`/instructors/assignment/${assignmentId}/remediations/${questions[currentPage-1].id}`)"
                      variant="info">Create Learning Tree
            </b-button>

            <toggle-button
              v-if="questionFilesAllowed && questions[currentPage-1].inAssignment"
              @change="toggleQuestionFiles(questions, currentPage, assignmentId, $noty)"
              :width="250"
              :value="questions[currentPage-1].questionFiles"
              :sync="true"
              :font-size="14"
              :margin="4"
              :color="{checked: '#007BFF', unchecked: '#75C791'}"
              :labels="{checked: 'Disable Question File Upload', unchecked: 'Enable Question File Upload'}"/>
          </div>
        </div>
        {{ showQuestions}}
{{ questions[currentPage-1].non_technology }}
        <iframe id="myIframe"
                allowtransparency="true" frameborder="0"
                v-bind:src="questions[currentPage-1].non_technology_iframe_src"
                style="width: 1px;min-width: 100%;"
                v-if="showQuestions && questions[currentPage-1].non_technology"
        ></iframe>
        <div v-html="questions[currentPage-1].technology_iframe"></div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import {ToggleButton} from 'vue-js-toggle-button'
import {toggleQuestionFiles} from '~/helpers/ToggleQuestionFiles'
import {h5pResizer} from "~/helpers/H5PResizer"
import {mapGetters} from "vuex";

export default {
  components: {
    VueBootstrapTypeahead,
    ToggleButton
  },
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({
    continueLoading: true,
    isLoading: false,
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
    showPage: false,
    title: ''
  }),
  created() {
    this.toggleQuestionFiles = toggleQuestionFiles
  },
  mounted() {
    if (this.user.role !== 2) {
      this.$noty.error("You do not have access to this page.")
      return false
    }
    this.isLoading = true
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentInfo()

  },
  methods: {
    async getAssignmentInfo() {
      try {
        const {data} = await axios.get(`/api/assignments/${this.assignmentId}`)
        if (data.has_submissions) {
          this.isLoading = false
          this.$noty.error("You can't add or remove questions from the assignment since students have already submitted responses.")
          this.continueLoading = false
        }
        this.title = `Add Questions to "${data.name}"`
        this.questionFilesAllowed = (data.submission_files === 'q')//can upload at the question level

      } catch (error) {
        console.log(error.message)
        this.title = 'Add Questions'
      }
      if (this.continueLoading) {//OK to load the rest of the page
        this.tags = this.getTags()
        h5pResizer()
      }
    },
    changePage(currentPage) {
      this.$nextTick(() => {
        let iframe_id = this.questions[currentPage - 1].iframe_id
        iFrameResize({log: true}, `#${iframe_id}`)
      })
    },
    removeTag(chosenTag) {
      this.chosenTags = _.without(this.chosenTags, chosenTag);
      console.log(this.chosenTags)
    }
    ,
    addTag() {
      console.log(this.query)
      if (this.query.includes("id=")) {
        let id = this.query.replace("id=", '')
        if (!((id >>> 0) === parseFloat(id))) {
          this.$noty.error("Your page id should be a positive integer.")
          return
        } else {
          this.chosenTags.push(this.query)
        }
      }
      if ((this.query !== '') && this.tags.includes(this.query) && !this.chosenTags.includes(this.query)) {
        this.chosenTags.push(this.query)
      }
      this.$refs.queryTypeahead.inputValue = this.query = '' //https://github.com/alexurquhart/vue-bootstrap-typeahead/issues/22
    }
    ,
    async getTags() {
      try {
        const {data} = await axios.get(`/api/tags`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        } else {
          this.tags = data.tags
          this.showPage = true
          this.isLoading = false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }

    }
    ,
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

    }
    ,
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

    }
    ,
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

        if (questionsByTags.type === 'success' && questionsByTags.questions.length > 0) {
          //get whether in the assignment and get the url
          const {data} = await axios.get(`/api/assignments/${this.assignmentId}/questions/question-info`)

          let questionInfo = data

          console.log(questionsByTags.questions)
          console.log('by assignment')
          console.log(questionInfo)
          if ((questionInfo.type === 'success')) {

            for (let i = 0; i < questionsByTags.questions.length; i++) {

              questionsByTags.questions[i].inAssignment = questionInfo.question_ids.includes(questionsByTags.questions[i].id)

              questionsByTags.questions[i].questionFiles = questionInfo.question_files.includes(questionsByTags.questions[i].id)
            }

            this.questions = questionsByTags.questions
            let iframe_id = this.questions[0].iframe_id;
            this.$nextTick(() => {
              iFrameResize({log: true}, `#${iframe_id}`)
              iFrameResize({ log: true }, '#myIframe')
            })
            // console.log(this.questions)
            this.showQuestions = true
          } else {
            this.$noty.error(questionIds.message)
          }
        } else {
          let timeout = questionsByTags.timeout ? questionsByTags.timeout : 6000
          this.$noty.error(questionsByTags.message, {timeout: timeout})
        }

      } catch (error) {
        this.$noty.error(error.message)
      }
      this.gettingQuestions = false
    }
    ,
    getStudentView(assignmentId) {
      this.$router.push(`/assignments/${assignmentId}/questions/view`)
    }
  }
  ,
  metaInfo() {
    return {title: this.$t('home')}
  }
}

</script>
<style>
body, html {
  overflow: visible;

}
</style>
