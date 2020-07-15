<template>
  <div>
    <div v-if="!initializing">
      <div v-if="questions.length">
        <div class="d-flex justify-content-between">
          <div class="mt-1 mb-2" v-on:click="getMoreQuestions()">
            <b-button variant="success">Get More Questions</b-button>
          </div>
          <div class="overflow-auto">
            <b-pagination
              v-model="currentPage"
              :total-rows="questions.length"
              :per-page="perPage"
            ></b-pagination>
          </div>
          <div class="mt-1 mb-2" v-on:click="removeQuestion(currentPage)">
            <b-button variant="danger">Remove Question</b-button>
          </div>
        </div>
        <b-card-text :items="questions">
          <b-embed type="iframe"
                   aspect="16by9"
                   v-bind:src="`https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=${ questions[currentPage-1].technology_id }`"
                   allowfullscreen
          ></b-embed>
        </b-card-text>
      </div>
      <div v-else>
        <div v-if="questions !== ['init']">
          <div class="mt-1 mb-2" v-on:click="getMoreQuestions()">
            <b-button variant="success">Get More Questions</b-button>
          </div>
          <div class="d-flex justify-content-center mt-5">
            <p>This assignment currently has no questions.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<script>
  import axios from 'axios'


  export default {

    middleware: 'auth',
    data: () => ({
      perPage: 1,
      currentPage: 1,
      questions: [],
      initializing: true //use to show a blank screen until all is loaded
    }),
    mounted() {
      this.assignmentId = this.$route.params.assignmentId
      this.getSelectedQuestions(this.assignmentId)
    },
    methods: {
      async getSelectedQuestions(assignmentId) {
        try {
          const {data} = await axios.get(`/api/assignments/${assignmentId}/questions/view`)
          this.questions = data
          this.initializing = false
        } catch (error) {
          alert(error)
          this.$noty.error('We could not retrieve the questions for this assignment.  Please try again or contact us for assistance.')
        }
      },
      getMoreQuestions() {
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
