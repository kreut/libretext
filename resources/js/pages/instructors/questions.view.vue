<template>
  <div>
   hello
  </div>
</template>

<script>
  import axios from 'axios'


  export default {

    middleware: 'auth',
    data: () => ({

    }),
    mounted() {
      //get all of the questions for the assignment
      //allow them to be removed


    },
    methods: {

    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
</script>
