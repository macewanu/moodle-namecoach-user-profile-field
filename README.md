# Moodle NameCoach user profile field
A custom user profile field type to display the NameCoach "Hear my name" audio widget on the Moodle user profile page.  
This is different from the delivered LTI integration from NameCoach.
See the [NameCoach website](https://www.name-coach.com/) for more information on NameCoach.

## How to install
Clone the repository into [moodle-root]/user/profile/field/namecoach.  
Run the admin upgrade script (web or cli).

## How to configure
Log into Moodle as a Moodle administrator.  
Go to Site administration / Users / Accounts / User profile fields.  
Create a new profile field of type NameCoach, and configure:
- Short name can be anything (e.g. "pronunciation")
- Name should make sense to users viewing a profile page (e.g. "Hear my name")
- Set general options as desired, or leave defaults
- You **must** enter a valid NameCoach API token in the NameCoach API token field
- Save changes

Note that the matching of the user with their NameCoach account depends on their Moodle email and their NameCoach email being the same.

## How to use
Once the field is properly configured, users can select Edit profile and select the option "Hear my name" (or whatever it was named in the configuration steps) by checking the box.
As long as the user has at least one recording tied to a standard name page or LTI name page available, the play-back widget will be displayed on the user's profile page.
(Note that the generic "name badge" recording, if the user recorded one, is not available through the API.)
