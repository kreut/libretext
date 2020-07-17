<template>
  <div>
    <PageTitle title="Grades"></PageTitle>
    <div v-if="hasAssignments">
      <b-table striped
               hover
               fixed
               :items="items"
               :fields="fields"
               :sort-by.sync="sortBy"
               :sort-desc.sync="sortDesc"
               sort-icon-left
               responsive="sm"
      ></b-table>
    </div>
    <div v-else>
      <b-alert show variant="warning"><a href="#" class="alert-link">Once you create your first assignment, you'll be able to view your gradebook.</a></b-alert>
  </div>
  </div>
</template>
<script>
  import axios from 'axios'
  import Form from "vform"

  // get all students enrolled in the course: course_enrollment
  // get all assignments for the course
  //
  export default {
    middleware: 'auth',
    data: () => ({
      sortBy: 'name',
      sortDesc: false,
      courseId: '',
      fields: [],
      grades: [],
      items: [],
      hasAssignments: true
    }),
    mounted() {
      this.courseId = this.$route.params.courseId
      this.getGrades();
    },
    methods: {
      getGrades() {

        try {
          axios.get(`/api/courses/${this.courseId}/grades`).then(
            response => {
              console.log(response)
              if (response.data.hasAssignments) {
                this.items = response.data.rows
                this.fields = response.data.fields
              } else {
                this.hasAssignments = false
              }
            }
          )
        } catch (error) {
          alert(error.message)
        }
      }

    }
  }
</script>
