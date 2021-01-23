<template>
  <div>
    <div v-if="canViewLearningTrees">
      <PageTitle title="My Learning Trees" />
      <div class="float-right mb-2">
        <b-button v-b-modal.modal-assignment-details class="mr-1" variant="primary"
                  @click="createLearningTree"
        >
          Create Learning Tree
        </b-button>
      </div>
      <b-modal
        id="modal-delete-learning-tree"
        ref="modal"
        title="Confirm Delete Learning Tree"
        ok-title="Yes, delete learning tree!"
        @ok="handleDeleteLearningTree"
      >
        <p>Please note that once a Learning Tree is deleted, it can not be retrieved.</p>
      </b-modal>
      <div v-if="hasLearningTrees">
        <b-table striped hover
                 :fields="fields"
                 :items="learningTrees"
        >
          <template v-slot:cell(created_at)="data">
            {{ $moment(data.item.created_at, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
          </template>
          <template v-slot:cell(actions)="data">
            <div class="mb-0">
              <b-tooltip ref="tooltip"
                         :target="getTooltipTarget('editLearningTree',data.item.id)"
                         delay="500"
              >
                Edit Learning Tree
              </b-tooltip>
              <b-tooltip :target="getTooltipTarget('createLearningTreeFromTemplate',data.item.id)"
                         triggers="hover"
                         delay="500"
              >
                Create Learning Tree From Template
              </b-tooltip>
              <span class="pr-1" @click="editLearningTree(data.item.id)">
                <b-icon :id="getTooltipTarget('editLearningTree',data.item.id)" icon="pencil" />
              </span>
              <span class="pr-1" @click="createLearningTreeFromTemplate(data.item.id)">
                <b-icon :id="getTooltipTarget('createLearningTreeFromTemplate',data.item.id)"
                        icon="clipboard-check"
                />
              </span>
              <b-tooltip :target="getTooltipTarget('deleteLearningTree',data.item.id)"
                         delay="500"
              >
                Delete Learning Tree
              </b-tooltip>
              <b-icon :id="getTooltipTarget('deleteLearningTree',data.item.id)" icon="trash" @click="deleteLearningTree(data.item.id)" />
            </div>
          </template>
        </b-table>
      </div>
      <div v-else>
        <br>
        <div class="mt-4">
          <b-alert :show="showNoLearningTreesAlert" variant="warning">
            <a href="#" class="alert-link">You currently have no
              Learning Trees.
            </a>
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'
import { getTooltipTarget, initTooltips } from '../../helpers/Tooptips'

export default {
  middleware: 'auth',
  data: () => ({
    fields: [
      'id',
      {
        key: 'title',
        sortable: true
      },
      {
        key: 'description',
        sortable: true
      },
      {
        key: 'created_at',
        label: 'Date Created',
        sortable: true
      },
      'actions'
    ],
    learningTrees: [],
    canViewLearningTrees: false,
    hasLearningTrees: false,
    showNoLearningTreesAlert: false
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.getLearningTrees()
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
  },
  methods: {
    async createLearningTreeFromTemplate (learningTreeId) {
      try {
        const { data } = await axios.post(`/api/learning-trees/${learningTreeId}/create-learning-tree-from-template`)
        this.$noty[data.type](data.message)
        await this.getLearningTrees()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleDeleteLearningTree () {
      try {
        const { data } = await axios.delete(`/api/learning-trees/${this.learningTreeId}`)
        this.$noty[data.type](data.message)
        this.$bvModal.hide('modal-delete-learning-tree')
        await this.getLearningTrees()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    deleteLearningTree (learningTreeId) {
      this.learningTreeId = learningTreeId
      this.$bvModal.show('modal-delete-learning-tree')
    },
    editLearningTree (learningTreeId) {
      this.$router.push(`/instructors/learning-trees/editor/${learningTreeId}`)
    },
    createLearningTree () {
      this.$router.push(`/instructors/learning-trees/editor/0`)
    },
    async getLearningTrees () {
      try {
        const { data } = await axios.get('/api/learning-trees')
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
  metaInfo () {
    return { title: this.$t('home') }
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
