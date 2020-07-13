<template>
    <div>
      <vue-bootstrap-typeahead
        v-model="query"
        :data="tags"
        placeholder="Enter a tag"
      />
      <p class="lead">
        Selected Tag: <strong>{{query}}</strong>
      </p>
      <b-button variant="primary" v-on:click="getQuestionsByTags()">Get questions</b-button>
    </div>
</template>

<script>
  import axios from 'axios'
  import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'

  export default {
    components: {
      VueBootstrapTypeahead
    },
    middleware: 'auth',
    data: () => {
      return {
        query: '',
        tags: [],
        questions: []
      }
    },
    mounted() {
      this.assignmentId = this.$route.params.assignmentId
      this.tags = this.getTags();

    },
    methods: {
      getTags() {
        try {
          axios.get(`/api/tags`).then(
            response => {
              console.log(response.data)
              this.tags = response.data
            })
        } catch (error) {
          alert(error.message)
        }

      },
      getQuestionsByTags() {
        try {
          axios.post(`/api/questions/getQuestionsByTags`, {'tags' : this.query }).then(
            response => {
              console.log(response.data)
              this.questions = response.data
            })
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
