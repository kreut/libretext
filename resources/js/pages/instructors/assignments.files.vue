<template>
  <div>
    I'm hjere
  </div>
</template>

<script>
  import axios from 'axios'
  import Form from "vform"


  export default {
    middleware: 'auth',
    data: () => ({}),
    mounted() {
      this.assignmentId = this.$route.params.assignmentId
      this.assignmentFiles = this.getAssignmentFiles(this.assignmentId)
    },
    methods: {
      async getAssignmentFiles() {
        const {data} = await axios.get(`/api/assignment-files/${this.assignmentId}`)
        console.log(data)
      }
    }
  }
</script>
