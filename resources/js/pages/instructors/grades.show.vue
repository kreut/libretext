<template>
  <div>
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
      items: []
    }),
    mounted() {
      this.courseId = this.$route.params.id
      this.getGrades();
    },
    methods: {
      getGrades() {

        try {
          axios.get('/api/grades/' + this.courseId).then(
            response => {
              this.items = response.data.rows
              this.fields = response.data.fields

              }

          )
        } catch (error) {
          alert(error.message)
        }
      }

    }
  }
  </script>
