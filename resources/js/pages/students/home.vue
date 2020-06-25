<template>
<div>
  <b-table striped hover :items="assignments"></b-table>
</div>
</template>

<script>
  import axios from 'axios'

  export default {
    middleware: 'auth',
    data: () => ({
      assignments: []
    }),
    mounted () {
      this.getAssignments();

    },
    methods: {
      getAssignments() {
        try {
          axios.get('/api/assignments').then(
            response => this.assignments = response.data
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
