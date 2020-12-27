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
      <div v-if="!isLoading">
        <PageTitle title="Question Properties" />
        <b-table
          striped
          hover
          :no-border-collapse="true"
          :fields="fields"
          :items="items"
        />
      </div>
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
    if (this.user.role !== 2) {
      this.$noty.error('You do not have access to the assignment questions page.')
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentInfo()
  },
  methods: {
    async getAssignmentInfo () {
      try {
        console.log('sdfds')
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/summary`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.fields = data.fields
        this.items = data.rows
        console.log(data)
      } catch (error) {

      }
      this.isLoading = false
    }
  }
}
</script>
