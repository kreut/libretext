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
              this.tags= response.data
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
