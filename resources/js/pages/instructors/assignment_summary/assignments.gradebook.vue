<template>
  <div>
    <PageTitle title="Gradebook By Question And Student" />
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <b-table
        striped
        hover
        :no-border-collapse="true"
        :fields="fields"
        :items="items"
      />
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  middleware: 'auth',
  components: {
    Loading
  },
  data: () => ({
    fields: [],
    items: [],
    isLoading: true
  }),
  mounted () {
    this.assignmentId = this.$route.params.assignmentId
    this.isLoading = true
    this.getAssignmentQuestionScoresByUser()
  },
  methods: {
    async getAssignmentQuestionScoresByUser () {
      try {
        const { data } = await axios.get(`/api/scores/assignment/${this.assignmentId}/get-assignment-questions-scores-by-user`)
        console.log(data)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.items = data.rows
        this.fields = data.fields
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
