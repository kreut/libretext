<template>
  <div>
    <div class="vld-parent">
      <PageTitle :title="title" />
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <b-container>
        <b-row>
          <div v-if="user.role === 2" class="col-md-3">
            <card title="Get Assessments" class="properties-card">
              <ul class="nav flex-column nav-pills">
                <li v-for="tab in tabs" :key="tab.route" class="nav-item">
                  <router-link :to="{ name: tab.route }" class="nav-link" active-class="active">
                    {{ tab.name }}
                  </router-link>
                </li>
              </ul>
            </card>
          </div>
          <p>
            Using the search box you can find Learning Trees by id.
          </p>
          <b-form-group
            id="learning_tree_id"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Learning Tree Id"
            label-for="Learning Tree Id"
          >
            <b-form-row>
              <b-col lg="3">
                <b-form-input
                  id="learning_tree_id"
                  v-model="learningTreeForm.learning_tree_id"
                  type="text"
                  placeholder=""
                  :class="{ 'is-invalid': learningTreeForm.errors.has('learning_tree_id') }"
                  @keydown="learningTreeForm.errors.clear('learning_tree_id')"
                />
                <has-error :form="learningTreeForm" field="learning_tree_id" />
              </b-col>
            </b-form-row>
          </b-form-group>
          <div class="mt-3 d-flex flex-row">
            <b-button variant="success" class="mr-2" @click="getLearningTreeById()">
              <b-spinner v-if="gettingLearningTree" small type="grow" />
              Get Learning Tree
            </b-button>
          </div>
        </b-row>
      </b-container>
      <hr>
    </div>

    <iframe v-if="learningTreeSrc.length > 0"
            allowtransparency="true"
            frameborder="0"
            :src="learningTreeSrc"
            style="width: 800px;min-width: 100%;height:800px"
    />
  </div>
</template>
<script>
import axios from 'axios'
import Form from 'vform'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'

export default {
  components: {
    Loading
  },
  middleware: 'auth',
  data: () => ({
    gettingLearningTree: false,
    learningTreeExists: false,
    isLoading: true,
    learningTreeSrc: '',
    title: '',
    learningTreeForm: new Form({
      learning_tree_id: ''
    })
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    tabs () {
      return [
        {
          icon: '',
          name: 'Questions',
          route: 'questions.get'
        },
        {
          icon: '',
          name: 'Learning Trees',
          route: 'learning_trees.get'
        }
      ]
    }
  },
  mounted () {
    if (this.user.role !== 2) {
      this.$noty.error('You do not have access to this page.')
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentInfo()
  },
  methods: {
    async getLearningTreeById () {
      try {
        if (!this.learningTreeForm.learning_tree_id) {
          this.$noty.error("You didn't enter a Learning Tree id.")
          return false
        }
        this.gettingLearningTree = true
        const { data } = await axios.post('/api/learning-trees/learning-tree-exists', { 'learning_tree_id': this.learningTreeForm.learning_tree_id })
        this.gettingLearningTree = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.learningTreeSrc = `/learning-trees/${this.learningTreeForm.learning_tree_id}/get`
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getAssignmentInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/get-questions-info`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let assignment = data.assignment
        this.hasSubmissions = assignment.hasSubmissions
        if (this.hasSubmissions) {
          this.$noty.error('This assignment is locked.  You can\'t add or remove Learning Trees from the assignment since students have already submitted responses.')
        }
        this.title = `Add Learning Trees to "${assignment.name}"`
        this.questionFilesAllowed = (assignment.submission_files === 'q')// can upload at the question level
      } catch (error) {
        console.log(error.message)
        this.title = 'Add Learning Trees'
      }
      this.isLoading = false
    }
  }
}
</script>
