<template>
  <div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <PageTitle v-if="!isLoading" title="Assignment Gradebook" />
      <b-table
        striped
        hover
        responsive
        sticky-header="800px"
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
import { mapGetters } from 'vuex'

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
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$noty.error('You do not have access to the assignment gradebook page.')
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
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
