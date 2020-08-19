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
    <iframe src="/storage/assignments/1/fake2.pdf"></iframe>
    <!--<pdf src="../storage/assignments/1/fake_2.pdf"></pdf>-->
    {{assignmentFiles[currentPage-1]}}
  </div>
</template>

<script>
  import axios from 'axios'
  import Form from "vform"
  import pdf from 'vue-pdf'


  export default {
    components: {
      pdf
    },
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
