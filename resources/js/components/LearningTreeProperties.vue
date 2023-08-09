<template>
  <div>
    <b-modal
      :id="modalId"
      ref="modal"
      title="Tree Properties"
      size="lg"
      no-close-on-backdrop
      :hide-footer="!canEditLearningTree"
      @hidden="$emit('resetLearningTreePropertiesModal')"
    >
      <RequiredText v-if="canEditLearningTree"/>
      <b-form ref="form">
        <b-form-group v-if="learningTreeId">
          <label for="learningTreeId" class="col-sm-4 col-lg-3 col-form-label pl-0">
            Learning Tree ID
          </label><span id="learningTreeId">{{ learningTreeId }}</span>
        </b-form-group>

        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="learning_tree_title"
        >
          <template v-slot:label>
            Title<span v-show="canEditLearningTree">*</span>
          </template>
          <b-form-input
            v-show="canEditLearningTree"
            id="learning_tree_title"
            v-model="learningTreeForm.title"
            type="text"
            :class="{ 'is-invalid': learningTreeForm.errors.has('title') }"
            @keydown="learningTreeForm.errors.clear('title')"
          />
          <has-error :form="learningTreeForm" field="title"/>
          <div v-show="!canEditLearningTree">
            {{ learningTreeForm.title }}
          </div>
        </b-form-group>

        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="description"
        >
          <template v-slot:label>
            Public Description<span v-show="canEditLearningTree">*</span>
          </template>
          <b-form-textarea
            v-if="canEditLearningTree"
            id="description"
            v-model="learningTreeForm.description"
            type="text"
            :class="{ 'is-invalid': learningTreeForm.errors.has('description') }"
            @keydown="learningTreeForm.errors.clear('description')"
          />
          <has-error :form="learningTreeForm" field="description"/>
          <div v-if="!canEditLearningTree">
            {{ learningTreeForm.description }}
          </div>
        </b-form-group>
        <b-form-group
          v-if="canEditLearningTree"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="description"
        >
          <template v-slot:label>
            Private Notes
          </template>
          <b-form-textarea
            id="description"
            v-model="learningTreeForm.notes"
            type="text"
            :class="{ 'is-invalid': learningTreeForm.errors.has('notes') }"
            @keydown="learningTreeForm.errors.clear('notes')"
          />
          <has-error :form="learningTreeForm" field="notes"/>
        </b-form-group>
      </b-form>
      <b-form-group
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="public"
      >
        <template v-slot:label>
          Public<span v-show="canEditLearningTree">*</span>
          <QuestionCircleTooltip id="public-learning-tree-tooltip"/>
          <b-tooltip target="public-learning-tree-tooltip"
                     delay="250"
                     triggers="hover focus"
          >
            Learning trees that are public can be used by any instructor. Learning trees that are not public are only
            accessible in searches
            by {{ canEditLearningTree ? 'you' : 'the tree\'s author' }}.
          </b-tooltip>
        </template>
        <b-form-row class="mt-2">
          <b-form-radio-group
            v-if="canEditLearningTree"
            id="public"
            v-model="learningTreeForm.public"
          >
            <b-form-radio name="public" value="1">
              Yes
            </b-form-radio>
            <b-form-radio name="public" value="0">
              No
            </b-form-radio>
          </b-form-radio-group>
          <div v-if="!canEditLearningTree">
            {{ Boolean(learningTreeForm.public) ? 'Yes' : 'No' }}
          </div>
        </b-form-row>
      </b-form-group>
      <template #modal-footer>
        <b-button size="sm"
                  @click="hideLearningTreePropertiesModal()"
        >
          Cancel
        </b-button>
        <b-button size="sm"
                  variant="primary"
                  @click="$emit('saveLearningTreeProperties')"
        >
          Save
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
export default {
  name: 'LearningTreeProperties',
  props: {
    canEditLearningTree: {
      type: Boolean,
      default: true
    },
    modalId: {
      type: String,
      default: 'modal-learning-tree-properties'
    },
    learningTreeId: {
      type: Number,
      default: 0
    },
    learningTreeForm: {
      type: Object,
      default: () => {
      }
    }
  },
  methods: {
    hideLearningTreePropertiesModal () {
      this.$bvModal.hide(this.modalId)
      this.$emit('resetLearningTreePropertiesModal')
    }
  }
}
</script>

<style scoped>

</style>
