<template>
  <b-container class="pb-3">
    <b-row class="text-center pb-3" align-v="center">
      <b-col>
        <div v-for="index in [0,1]" :key="`action-to-take-${index}`" class="pb-3">
          <b-card class="action-to-take">
            Action To Take:
            <div v-if="!selectedActionsToTake[index]">
              None selected
            </div>
            <div v-else>
              {{
                qtiJson.actionsToTake.find(actionToTake => actionToTake.identifier === selectedActionsToTake[index]).value
              }}
            </div>
          </b-card>
        </div>
      </b-col>
      <b-col>
        <b-card class="potential-conditions">
          Condition Most Likely Experiencing:
          <div v-if="!selectedPotentialCondition.length">
            None selected
          </div>
          <div v-else>
            {{
              qtiJson.potentialConditions.find(potentialCondition => potentialCondition.identifier === selectedPotentialCondition[0]).value
            }}
          </div>
        </b-card>
      </b-col>
      <b-col>
        <div v-for="index in [0,1]" :key="`parameters-to-monitor-${index}`" class="pb-3">
          <b-card class="parameters-to-monitor">
            Parameter To Monitor:
            <div v-if="!selectedParametersToMonitor[index]">
              None selected
            </div>
            <div v-else>
              {{
                qtiJson.parametersToMonitor.find(parameterToMonitor => parameterToMonitor.identifier === selectedParametersToMonitor[index]).value
              }}
            </div>
          </b-card>
        </div>
      </b-col>
    </b-row>
    <b-row>
      <b-col>
        <b-card
          no-body
        >
          <b-list-group flush>
            <b-list-group-item class="bow-tie list-group-item-header font-weight-bold text-center"
                               style="font-size:14px"
            >
              <h3 id="actions-to-takel">Actions To Take</h3>
            </b-list-group-item>
            <b-form>
              <b-form-checkbox-group
                v-model="selectedActionsToTake"
                role="group"
                aria-labelledby="actions-to-take"
              >
                <b-list-group-item v-for="(actionToTake, actionToTakeIndex) in qtiJson.actionsToTake"
                                   :key="`action-to-take-${actionToTakeIndex}`"
                                   style="font-size:12px"
                                   class="m-1 action-to-take"
                >
                  <b-form-checkbox :value="actionToTake.identifier"
                                   :aria-checked="selectedActionsToTake.includes(actionToTake.identifier)">
                    {{ actionToTake.value }}
                    <CheckBoxResponseFeedback
                      v-if="qtiJson.studentResponse && showResponseFeedback"
                      :key="`response-feedback-action-to-take-${actionToTakeIndex}`"
                      :identifier="actionToTake.identifier"
                      :responses="qtiJson.actionsToTake"
                      :student-response="qtiJson.studentResponse.actionsToTake"
                    />
                  </b-form-checkbox>
                </b-list-group-item>
              </b-form-checkbox-group>
            </b-form>
          </b-list-group>
        </b-card>
      </b-col>
      <b-col>
        <b-card
          no-body
        >
          <b-list-group flush>
            <b-list-group-item class="bow-tie list-group-item-header font-weight-bold text-center"
                               style="font-size:14px"
            >
              <h3 id="potential-conditions">Potential Conditions</h3>
            </b-list-group-item>
            <b-form>
              <b-form-checkbox-group
                v-model="selectedPotentialCondition"
                role="group"
                aria-labelledby="potential-conditions"
              >
                <b-list-group-item v-for="(potentialCondition, potentialConditionIndex) in qtiJson.potentialConditions"
                                   :key="`potential-condition-${ potentialConditionIndex}`"
                                   style="font-size:12px"
                                   class="m-1 potential-conditions"
                >
                  <b-form-checkbox :value="potentialCondition.identifier"
                                   :aria-checked="selectedPotentialCondition.includes(potentialCondition.identifier)">
                    {{
                      potentialCondition.value
                    }}
                    <CheckBoxResponseFeedback
                      v-if="qtiJson.studentResponse && showResponseFeedback"
                      :key="`response-feedback-action-to-take-${potentialConditionIndex}`"
                      :identifier="potentialCondition.identifier"
                      :responses="qtiJson.potentialConditions"
                      :student-response="qtiJson.studentResponse.potentialConditions"
                    />
                  </b-form-checkbox>
                </b-list-group-item>
              </b-form-checkbox-group>
            </b-form>
          </b-list-group>
        </b-card>
      </b-col>
      <b-col>
        <b-card
          no-body
        >
          <b-list-group flush>
            <b-list-group-item class="bow-tie list-group-item-header font-weight-bold text-center pb-1"
                               style="font-size:14px"
            >
              <h3 id="parameters-to-monitor">Parameters To Monitor</h3>
            </b-list-group-item>
            <b-form>
              <b-form-checkbox-group
                v-model="selectedParametersToMonitor"
                role="group"
                aria-labelledby="parameters-to-monitor"
              >
                <b-list-group-item v-for="(parameterToMonitor, parameterToMonitorIndex) in qtiJson.parametersToMonitor"
                                   :key="`potential-condition-${ parameterToMonitorIndex}`"
                                   style="font-size:12px"
                                   class="m-1 parameters-to-monitor"
                >
                  <b-form-checkbox :value="parameterToMonitor.identifier"
                                   :aria-checked="selectedParametersToMonitor.includes(parameterToMonitor.identifier)"
                  >
                    {{ parameterToMonitor.value }}
                    <CheckBoxResponseFeedback
                      v-if="qtiJson.studentResponse && showResponseFeedback"
                      :key="`response-feedback-action-to-take-${parameterToMonitorIndex}`"
                      :identifier="parameterToMonitor.identifier"
                      :responses="qtiJson.parametersToMonitor"
                      :student-response="qtiJson.studentResponse.parametersToMonitor"
                    />
                  </b-form-checkbox>
                </b-list-group-item>
              </b-form-checkbox-group>
            </b-form>
          </b-list-group>
        </b-card>
      </b-col>
    </b-row>
    <GeneralFeedback :feedback="qtiJson.feedback" :feedback-type="feedbackType"/>
  </b-container>
</template>

<script>
import CheckBoxResponseFeedback
  from '../feedback/CheckBoxResponseFeedback'
import GeneralFeedback from '../feedback/GeneralFeedback'

export default {
  name: 'BowTieViewer',
  components: {
    CheckBoxResponseFeedback,
    GeneralFeedback
  },
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    showResponseFeedback: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    selectedActionsToTake: [],
    selectedPotentialCondition: [],
    selectedParametersToMonitor: [],
    feedbackType: ''
  }),
  watch: {
    selectedActionsToTake: function (newSelectedActionsToTake, oldSelectedActionsToTake) {
      if (newSelectedActionsToTake.length > 2) {
        this.$noty.info('Only 2 Actions To Take should be selected. Please de-select 1 of your choices.')
      }
    },
    selectedPotentialCondition: function (newSelectedPotentialCondition, oldSelectedPotentialCondition) {
      if (newSelectedPotentialCondition.length > 1) {
        this.$noty.info('Only 1 Potential Condition should be selected. Please de-select 1 of your choices.')
      }
    },
    selectedParametersToMonitor: function (newSelectedParametersToMonitor, oldSelectedParametersToMonitor) {
      if (newSelectedParametersToMonitor.length > 2) {
        this.$noty.info('Only 2 Parameters To Monitor should be selected. Please de-select 1 of your choices.')
      }
    }
  },
  mounted () {
    if (this.qtiJson.studentResponse) {
      this.feedbackType = 'correct'
      this.addSelected('actionsToTake', 'selectedActionsToTake')
      this.updateFeedbackType('actionsToTake')
      this.addSelected('potentialConditions', 'selectedPotentialCondition')
      this.updateFeedbackType('potentialConditions')
      this.addSelected('parametersToMonitor', 'selectedParametersToMonitor')
      this.updateFeedbackType('parametersToMonitor')
    }
  },
  methods: {
    updateFeedbackType (group) {
      for (let i = 0; i < this.qtiJson[group].length; i++) {
        let item = this.qtiJson[group][i]
        let identifier = item.identifier
        if ((item.correctResponse && !this.qtiJson.studentResponse[group].includes(identifier)) ||
          (!item.correctResponse && this.qtiJson.studentResponse[group].includes(identifier))) {
          this.feedbackType = 'incorrect'
        }
      }
    },
    addSelected (group, selected) {
      for (let i = 0; i < this.qtiJson.studentResponse[group].length; i++) {
        let identifier = this.qtiJson.studentResponse[group][i]
        this[selected].push(identifier)
      }
    }
  }
}
</script>
<style scoped>
div.bow-tie.list-group-item-header {
  height: 40px
}

h3 {
  font-weight: 700 !important;
  font-size: 14px;
  margin-bottom: 0;
}

.action-to-take {
  background-color: #E7F0FC;
}

.potential-conditions {
  background-color: #C3E9F2;
}

.parameters-to-monitor {
  background-color: #EDF1F5;
}

.bow-tie.list-group-item-header {
  padding: 10px;
  height: 50px;
  background-color: #EDF5F4;
}
</style>
