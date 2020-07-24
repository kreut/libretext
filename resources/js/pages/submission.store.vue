<template>
  <div>
    {{ submission }}
  </div>
</template>

<script>
  import axios from 'axios'

  export default {
    middleware: 'auth',
    data: () => ({
      submission: {'key' : 20, 'another_key' : 15}
    }),
    mounted() {
       const {data} = axios.post(`/api/submissions`, {'data' : this.submission})
      console.log(data)
    },
    methods: {

    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
</script>
