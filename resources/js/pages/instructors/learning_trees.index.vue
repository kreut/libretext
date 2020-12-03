<template>
  <div>
    <PageTitle v-if="canViewLearningTrees" title="My Learning Trees"></PageTitle>
    <div v-if="hasLearningTrees">
      <b-table striped hover
               :fields="fields"
               :items="learningTrees">

        <template v-slot:cell(created_at)="data">
          {{ $moment(data.item.created_at, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
        </template>
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
      {
        key: 'actions',
        sortable: false
      }
    ],
    learningTrees: [],
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
