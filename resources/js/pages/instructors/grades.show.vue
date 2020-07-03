<template>
  <div>
    <b-table striped hover :items="items"></b-table>
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
              this.items = response.data

              }

          )
        } catch (error) {
          alert(error.message)
        }
      }

    }
  }
  </script>
