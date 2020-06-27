<template>
<div>
  <b-table striped hover :fields="fields" :items="courses">
    <template v-slot:cell(name)="data">
      <a :href="`/courses/${data.item.id}`">{{ data.item.name }}</a>
    </template>
  </b-table>
</div>
</template>

<script>
  import axios from 'axios'

  export default {
    middleware: 'auth',
    data: () => ({
      fields: [
        {key: 'name', label: 'Course'},
        'start_date',
        'end_date'
  ],
      courses: []
    }),
    mounted () {
      this.getCourses();

    },
    methods: {
      getCourses() {
        try {
          axios.get('/api/courses').then(
            response => this.courses = response.data
          )
        } catch (error) {
          alert(error.response)
        }
      }
    },
    metaInfo () {
      return { title: this.$t('home') }
    }
  }
</script>
