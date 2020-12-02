<template>
  <div>
    <PageTitle v-if="canViewLearningTrees" title="My Learning Trees"></PageTitle>
    <div v-if="user.role === 2">
      <div class="row mb-4 float-right" v-if="canViewLearningTrees">
        <b-button variant="primary" v-b-modal.modal-learning-tree-details>Add Learning Trees</b-button>
      </div>
    </div>

    <b-modal
      id="modal-learning-tree-details"
      ref="modal"
      title="Learning Tree Details"
      @ok="submitLearningTreeInfo"
      @hidden="resetLearningTreeDetailsModal"
      ok-title="Submit"

    >
      <b-form ref="form">
        <b-form-group
          id="title"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Title"
          label-for="title"
        >
          <b-form-input
            id="title"
            v-model="learningTreeForm.title"
            type="text"
            :class="{ 'is-invalid': learningTreeForm.errors.has('title') }"
            @keydown="learningTreeForm.errors.clear('title')"
          >
          </b-form-input>
          <has-error :form ="learningTreeForm" field="title"></has-error>
        </b-form-group>

        <b-form-group
          id="description"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Description"
          label-for="description"
        >
          <b-form-textarea
            id="description"
            v-model="learningTreeForm.description"
            type="text"
            :class="{ 'is-invalid': learningTreeForm.errors.has('description') }"
            @keydown="learningTreeForm.errors.clear('description')"
          >
          </b-form-textarea>
          <has-error :form ="learningTreeForm" field="description"></has-error>
        </b-form-group>
      </b-form>
    </b-modal>

    <b-modal
      id="modal-delete-learning-tree"
      ref="modal"
      title="Confirm Delete Learning Tree"
      @ok="handleDeleteLearningTree"
      @hidden="resetLearningTreeDetailsModal"
      ok-title="Yes, delete learning tree!"

    >
      <p>Please note that once a Learning Tree is deleted, it can not be retrieved.</p>
    </b-modal>

    <div v-if="hasLearningTrees">
      <b-table striped hover
               :fields="fields"
               :items="learningTrees">

        <template v-slot:cell(actions)="data">
          <div class="mb-0">
            <b-tooltip ref="tooltip"
                       :target="getTooltipTarget('pencil',data.item.id)"
                       delay="500"
            >
              Edit Learning Tree
            </b-tooltip>
            <span class="pr-1" v-on:click="editLearningTree(data.item)">
                  <b-icon :id="getTooltipTarget('pencil',data.item.id)" icon="pencil"></b-icon>
                </span>
            <b-tooltip :target="getTooltipTarget('deleteLearningTree',data.item.id)"
                       delay="500">
              Delete Learning Tree
            </b-tooltip>
            <b-icon :id="getTooltipTarget('deleteLearningTree',data.item.id)" icon="trash"
                    v-on:click="deleteLearningTree(data.item.id)"></b-icon>

          </div>
        </template>
      </b-table>
    </div>
    <div v-else>
      <br>
      <div class="mt-4">
        <b-alert :show="showNoLearningTreesAlert" variant="warning"><a href="#" class="alert-link">You currently have no
          Learning Trees.
        </a></b-alert>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from "vform"
import {mapGetters} from "vuex"
import {getTooltipTarget} from '../../helpers/Tooptips'
import {initTooltips} from "../../helpers/Tooptips"

const now = new Date()
export default {
  middleware: 'auth',
  computed: mapGetters({
    user: 'auth/user'
  }),
  data: () => ({
    fields: [
      {
        key: 'title',
        label: 'Title'
      },
      {
        key: 'description'
      },
      'actions'
    ],
    learningTrees: [],
    learningTreeForm: new Form({
      title: '',
      description: '',
    }),
    canViewLearningTrees: false,
    hasLearningTrees: false,
    showNoLearningTreesAlert: false
  }),
  mounted() {
    this.getLearningTrees()
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)

  },
  methods: {
    deleteLearningTree(learningTreeId) {
      this.learningTreeId = learningTreeId
      this.$bvModal.show('modal-delete-learning-tree')
    },
    async handleDeleteLearningTree() {
      try {
        const {data} = await axios.delete(`/api/learning-trees/${this.learningTreeId}`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-delete-learning-tree')
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
    ,
    editLearningTree(learning_tree) {
      this.$refs.tooltip.$emit('close')
      this.learningTreeId = learning_tree.id;
      this.learningTreeForm.title = learning_tree.title
      this.learningTreeForm.description = learning_tree.description
      this.$bvModal.show('modal-learning-tree-details')
    },
    resetLearningTreeDetailsModal() {
      this.learningTreeForm.title = ''
      this.learningTreeForm.description  = ''
      this.learningTreeId = false
      this.learningTreeForm.errors.clear()
    }
    ,
    resetAll(modalId) {
      this.getLearningTrees()
      this.resetLearningTreeDetailsModal()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    }
    ,
    submitLearningTreeInfo(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      !this.learningTreeId ? this.createLearningTree() : this.updateLearningTree()
    }
    ,
    async createLearningTree() {
      try {
        const {data} = await this.learningTreeForm.post('/api/courses')
        this.$noty[data.type](data.message)
        this.resetAll('modal-learning-tree-details')

      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }

    },
    async updateLearningTree() {
      try {
        const {data} = await this.learningTreeForm.post(`/api/learning-trees/info/${this.learningTreeId}`)
        this.$noty[data.type](data.message)
        this.resetAll('modal-learning-tree-details')

      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }

      }

    }
    ,
    async getLearningTrees() {
      try {
        const {data} = await axios.get('/api/learning-trees')
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.canViewLearningTrees = true
          this.hasLearningTrees = data.learning_trees.length > 0
          this.showNoLearningTreesAlert = !this.hasLearningTrees
          this.learningTrees = data.learning_trees
          console.log(data.learning_trees)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  },
  metaInfo() {
    return {title: this.$t('home')}
  }
}
</script>
<style>
body, html {
  overflow: visible;

}

svg:focus, svg:active:focus {
  outline: none !important;
}
</style>
