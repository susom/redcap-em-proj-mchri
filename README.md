# MCHRI Module

This is a project specific EM created specifically for the MCHRI Grant Proposal project.
This was ported from the plugins project.

There are three EM links:

###1. Landing Page 
* Entry point for the reviewers. They will not be users of the REDCap project and will enter without project context.
* There are three links that should be supported:
    *  From the REDCap left hand menu (for use by REDCap users)
       
       http://redcap.stanford.edu/redcap_v10.6.3/ExternalModules/?prefix=proj_mchri&page=src%2Flanding&pid=21085
  *  Sent to the reviewer via alert. Link has 'projectId=<pid>' postpended

     http://redcap.stanford.edu/redcap_v10.6.3/ExternalModules/?prefix=proj_mchri&page=src%2Flanding&projectId=21085
    
  * First attempt to fix, we sent out api no auth links. These do not force webauth.
    
    https://redcap.stanford.edu/api/?type=module&prefix=proj_mchri&page=src%2Flanding&NOAUTH&pid=21085

###2. Reviewer Report
* Access limited to REDCAp project user. It needs to be called in the context of a record
* Entry will be within project context.

###3. Reviewer Meeting Report
* Access limited to REDCAp project user. It needs to be called in the context of a record
* Entry will be within project context.

----------
The reviewers will see different downloads depending on which submission round and program was selected:

* Round 1
  * budget_field   -- 'budget_worksheet'
  * proposal_field -- 'chri_proposal'
* Round 2
  * Program is 2 (Clinical Trainee (MD) Support Program): 
      * budget_field   -- 'resub_budget_upload'
      * proposal_field -- 'resub_application_upload'
  * Program is 4 (Faculty Scholars Program), 5 (Harman Scholar Program), or 7 (	Transdisciplinary Initiatives Program):
    
      * budget_field   -- 'budget_worksheet_v2'
      * proposal_field -- 'full_tip_proposal'