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
      data: ''
    }),
    mounted() {
      try {
      axios.get(`/api/h5p`).then(
        response => {
          console.log(response);
          this.data = response.data
        })
      } catch (error) {
        alert(error.message)
      }
    },
    methods: {},
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
</script>
