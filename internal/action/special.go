package action

import (
	"net/http"
)

// SubIdAction — Route to a different URL based on a sub_id value.
type SubIdAction struct{}
func (a *SubIdAction) Type() string { return "SubId" }
func (a *SubIdAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	// Simple mapping from action_payload
	if mappings, ok := ctx.Stream.ActionPayload["mappings"].(map[string]interface{}); ok {
		val := ctx.Click.SubID1 // Assuming sub_id_1 is the mapping key
		if target, ok := mappings[val].(string); ok {
			http.Redirect(w, r, target, http.StatusFound)
			return nil
		}
	}
	// Fallback redirect
	http.Redirect(w, r, ctx.RedirectURL, http.StatusFound)
	return nil
}

// ToCampaignAction — Internal redirect to another campaign. (Max 10 hops)
type ToCampaignAction struct{}
func (a *ToCampaignAction) Type() string { return "ToCampaign" }
func (a *ToCampaignAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	if alias, ok := ctx.Stream.ActionPayload["campaign_alias"].(string); ok {
		// Signal to the pipeline to redispatch with the new campaign alias
		ctx.Click.CampaignAlias = alias
		return ErrRedispatch
	}
	return nil
}
