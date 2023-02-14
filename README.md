# AutoPlaylist - 2019

**[Abandoned project]**

AutoPlaylist was my first solo project with Symfony, started right after my Symfony classes ended. So no doubt it's really clunky and includes a lot of bad practices.

The purpose of this website was to let **Youtube** users watch a playlist of their favorites channels, to **always** and **quickly** have something to watch you'd **really** like.

Users had to select their favorite channels among their own subscriptions. Then, a Youtube playlist is created on their account with the most recents and unwatched videos of the selected channels.
This was done using Youtube's API but I wasn't aware of quotas and pricing at the time. Since a playlist creation and a video addition/removal via the API had a high cost, I could only hold 5-10 users to remain in the free quotas. That's why I abandoned this project.

Later, Youtube added a feature to select the videos you didn't finish among your subscriptions, which solve a part of the initial problem.

I still think this idea have potential but it have to be reworked to avoid quotas limitations. Maybe by not creating a Youtube playlist and just embed the videos iframes on the website itself ?
