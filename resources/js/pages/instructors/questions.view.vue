<template>
  <div>
  <div class="overflow-auto d-flex justify-content-center">
    <b-pagination
      v-model="currentPage"
        :total-rows="questions.length"
      :per-page="perPage"
    ></b-pagination>
  </div>
    <b-card-text :items="questions">
      <b-embed type="iframe"
               aspect="16by9"
               v-bind:src="`https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=${ questions[currentPage-1].technology_id}`"
               allowfullscreen
      ></b-embed>
    </b-card-text>
  </div>
</template>


<script>
  import axios from 'axios'


  export default {

    middleware: 'auth',
    data: () => ({
      perPage: 1,
      currentPage: 1,
      questions: []
    }),
    mounted() {
      this.assignmentId = this.$route.params.assignmentId
      this.getQuestions(this.assignmentId)
    },
    methods: {
      async getQuestions(assignmentId) {
        try {
          const {data} = await axios.get(`/api/assignments/${assignmentId}/questions/view`)
          this.questions = data
          console.log(data)
        } catch (error) {
          alert(error)
          this.$noty.error('We could not retrieve the questions for this assignment.  Please try again or contact us for assistance.')
        }
      }
      //what if there are no questions?
      //get all of the questions for the assignment
      //allow them to be removed


    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
</script>
