<template>
  <div>
    {{ data }}
  </div>
</template>

<script>
  import axios from 'axios'

  export default {
    middleware: 'auth',
    data: () => ({
      results: ''
    }),
    mounted() {
      this.sendResults(this.results)
    },
    methods: {
      async sendResults(results) {
        try {
          const {data} = await axios.post(`/api/results`, results)
          console.log(data)
        } catch (error) {
          alert(error.message)
        }
      }
    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
</script>
