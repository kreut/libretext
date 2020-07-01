<template>
  <div>
   Hello!
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
      fields: [
        {
          key: 'name',
          label: 'Course'
        },
        {
          key: 'start_date',
          formatter: value => {
            return formatDate(value)
          }
        },
        {
          key: 'end_date',
          formatter: value => {
            return formatDate(value)
          }
        },
        'actions'
      ],
      courses: [],
      courseId: false, //if there's a courseId it's an update
      min: new Date(now.getFullYear(), now.getMonth(), now.getDate()),
      form: new Form({
        name: '',
        start_date: '',
        end_date: ''
      })
    }),
    mounted() {
      this.getCourses();

    },
    methods: {}
  }
  </script>
