<template>
  <div>
    <div class="overflow-auto" v-if="assignmentFiles.length>0">
      <b-pagination
        v-model="currentPage"
        :total-rows="assignmentFiles.length"
        :per-page="perPage"
        align="center"
        first-number
        last-number
      ></b-pagination>
    </div>
    {{assignmentFiles[currentPage-1]}}
  </div>
</template>

<script>
  import axios from 'axios'
  import Form from "vform"


  export default {
    middleware: 'auth',
    data: () => ({
      currentPage: 1,
      perPage: 1,
      assignmentFiles: []
    }),
    mounted() {
      this.assignmentId = this.$route.params.assignmentId
      this.assignmentFiles = this.getAssignmentFiles(this.assignmentId)
    },
    methods: {
      async getAssignmentFiles() {
        const {data} = await axios.get(`/api/assignment-files/${this.assignmentId}`)
        console.log(data)
        this.assignmentFiles = data
      }
    }
  }
</script>
