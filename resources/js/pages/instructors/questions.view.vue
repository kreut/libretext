<template>
  <div>

      <div v-if="!initializing">
        <PageTitle v-bind:title="this.title"></PageTitle>
        <div v-if="questions.length">
          <div class="d-flex justify-content-between">
            <div class="mt-1 mb-2" v-on:click="getQuestions()" v-if="user.role !== 3">
              <b-button variant="success">Get Questions</b-button>
            </div>
            <div class="overflow-auto">
              <b-pagination
                v-model="currentPage"
                :total-rows="questions.length"
                :per-page="perPage"
                first-number
                last-number
              ></b-pagination>
            </div>
            <div class="mt-1 mb-2" v-on:click="removeQuestion(currentPage)" v-if="user.role !== 3">
              <b-button variant="danger">Remove Question</b-button>
            </div>
          </div>
          <b-card-text :items="questions">
            <b-embed type="iframe"
                     aspect="16by9"
                     v-bind:src="questions[currentPage-1].src"
                     allowfullscreen
            ></b-embed>
          </b-card-text>
        </div>
        <div v-else>
          <div v-if="questions !== ['init']">
            <div class="mt-1 mb-2" v-on:click="getQuestions()" v-if="user.role !== 3">
              <b-button variant="success">Get More Questions</b-button>
            </div>
            <div class="mt-4">
              <b-alert :show="true" variant="warning"><a href="#" class="alert-link">This assignment currently has no questions.
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
  import { getSrc } from '~/helpers/Questions'

  export default {

    middleware: 'auth',
    computed: mapGetters({
      user: 'auth/user',
      token: 'auth/token'
    }),
    data: () => ({
      perPage: 1,
      currentPage: 1,
      questions: [],
      initializing: true, //use to show a blank screen until all is loaded
      title: '',
      assignmentId: ''
    }),
    created() {
      this.getSrc = getSrc
      console.log(this.token)
    },
    mounted() {
      this.assignmentId = this.$route.params.assignmentId
      this.getTitle(this.assignmentId)
      this.getSelectedQuestions(this.assignmentId)
      let vm = this
      if (this.user.role === 3) {
        let receiveMessage = function (event) {
          if (event.data.action !== 'hello') {
            let submission_data = {
              'submission': event.data,
              'assignment_id': vm.assignmentId,
              'question_id': vm.questions[vm.currentPage - 1].id
            }
            console.log(submission_data)
            axios.post('/api/submissions', submission_data)
          } else {
            console.log ('Hello Event')
          }
        }
        window.addEventListener("message", receiveMessage, true);
      }
    },
    methods: {
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
          this.questions = data
          for (let i = 0; i < this.questions.length; i++) {
            this.questions[i].src = this.getSrc(this.questions[i], this.token)
          }

          this.initializing = false
        } catch (error) {
          alert(error)
          this.$noty.error('We could not retrieve the questions for this assignment.  Please try again or contact us for assistance.')
        }
      },
      getQuestions() {
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
