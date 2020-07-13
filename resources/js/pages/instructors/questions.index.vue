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
    <ul id="example-1">
      <li v-for="question in questions" :key="question.id">
        {{ question.title }}
        {{ question.author }}
        {{ question.technology_id }}
        <b-embed @load="questionLoaded()"
          type="iframe"
          aspect="16by9"
          v-bind:src="`https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=${question.technology_id}`"
          allowfullscreen
        ></b-embed>
      </li>
    </ul>
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
    data: () => ({
      fields: [
        'title',
        'author',
        {
          key: 'technology_id',
          label: 'Question'
        },
        'actions'
      ],
      query: '',
      tags: [],
      questions: []
    }),
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
      async getQuestionsByTags() {
        try {
          if (this.query === '') {
            this.$noty.error('Please enter at least one tag.')
            return false
          }
          const {data} = await axios.post(`/api/questions/getQuestionsByTags`, {'tags': this.query})
          if (data.type === 'success') {
            this.questions = data.questions
          } else {
            this.$noty.error(`There are no questions associated with <strong>${this.query}</strong>.`)
          }

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
