<template>
  <div>
    <vue-bootstrap-typeahead
      v-model="query"
      :data="tags"
      placeholder="Enter a tag"
    />
    <div class="mt-3">
      <b-button variant="primary" v-on:click="getQuestionsByTags()">Get questions</b-button>
      <b-button variant="primary" v-on:click="getSelectedQuestions()">View selected questions</b-button>
      <b-button variant="primary" v-on:click="getStudentView()">Student View</b-button>
    </div>
    <div v-for="question in questions" :key="question.id" class="mt-5">
      <b-card v-bind:title="question.title" v-bind:sub-title="question.author">
        <div class="mt-1 mb-2" v-on:click="addQuestion(question.id)">
          <v-button>Add</v-button>
        </div>
        <b-card-text>
          <b-embed @load="questionLoaded()"
                   type="iframe"
                   aspect="16by9"
                   v-bind:src="`https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=${question.technology_id}`"
                   allowfullscreen
          ></b-embed>
        </b-card-text>
      </b-card>
    </div>
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
      async addQuestion(questionId){
        try {
        const {data} = await axios.post(`/api/assignments/${this.assignmentId}/questions/${questionId}`)
          console.log(data)

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
