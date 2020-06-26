<template>
  <div>
    Create course!
  </div>
</template>

<script>
  import axios from 'axios'

  export default {
    middleware: 'auth',
    data: () => ({

    }),
    mounted () {


    },
    methods: {

    },
    metaInfo () {
      return { title: this.$t('home') }
    }
  }
</script>
