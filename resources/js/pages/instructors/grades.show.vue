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
      fields: [],
      grades: []
    }),
    mounted() {
      this.getGrades();

    },
    methods: {
      getGrades() {
        this.grades = ['f']
      }

    }
  }
  </script>
