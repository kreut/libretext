<template>
  <div>
    {{ grades }}
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
      grades: []
    }),
    mounted() {
      this.courseId = this.$route.params.id
      this.getGrades();
    },
    methods: {
      getGrades() {

        try {
          axios.get('/api/grades/' + this.courseId).then(
            response => this.grades = response.data
          )
        } catch (error) {
          alert(error.message)
        }
      }

    }
  }
  </script>
