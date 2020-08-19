<template>
  <div v-if="assignmentFiles.length>0">
    <div class="overflow-auto" >
      <b-pagination
        v-on:input="changePage"
        v-model="currentPage"
        :total-rows="assignmentFiles.length"
        :per-page="perPage"
        align="center"
        first-number
        last-number
      ></b-pagination>
    </div>
      <iframe v-if="assignmentFiles.length>0" :src="getAssignmentUrl(currentPage)"></iframe>
  </div>
</template>

<script>
  import axios from 'axios'
  import Form from "vform"
  //import pdf from 'vue-pdf'


  export default {
    /*components: {
      //pdf
    },*/
    middleware: 'auth',
    data: () => ({
      currentPage: 1,
      perPage: 1,
      assignmentFiles: []
    }),
    created() {
      this.assignmentId = this.$route.params.assignmentId
      this.assignmentFiles = this.getAssignmentFiles(this.assignmentId)
    },
    methods: {
      async changePage(currentPage){
        if (this.assignmentFiles[currentPage-1]['submission']) {
          const {data} = await axios.post('/api/assignment-files/get-temporary-url',
            {'assignment_id': this.assignmentId,
              'submission': this.assignmentFiles[currentPage - 1]['submission']
            })
          this.assignmentFiles[currentPage - 1]['url'] = data
        }
      },
      getAssignmentUrl(currentPage){
        return this.assignmentFiles[currentPage-1]['url']
      },
      assignmentUrlExists(currentPage){
        return (this.assignmentFiles[currentPage-1]['url'] !== null)
      },
      async getAssignmentFiles() {
        const {data} = await axios.get(`/api/assignment-files/${this.assignmentId}`)
        console.log(data)
        this.assignmentFiles = data
      }
    }
  }
</script>
