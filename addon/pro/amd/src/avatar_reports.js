define([
    "jquery",
    "core/modal_factory",
    "core/modal_events",
    "core/fragment",
    "core/ajax",
    "core/notification",
    "core/str",
], ($, ModalFactory, ModalEvents, Fragment, Ajax, Notification, Str) => {

    var SELECTORS = {
        ASSIGN_AVATAR_BTN: '.avatar-assign-btn',
        UPGRADE_AVATAR_BTN: 'table#avatar_report td a.avatar-upgrade-btn',
        usageItem: 'table#avatar_report td a.usage-items',
    };


    /**
     * Initialize the modal
     *
     * @param {int} cmid Course module ID
     * @param {int} userid User ID
     * @return {Promise}
     */
    var assignAvatar = (cmid, userid) => {

        return ModalFactory.create(
            {
                type: ModalFactory.types.DEFAULT,
                title: Str.get_string("assignnewavatar", "mod_avatar"),
                body: Fragment.loadFragment("avataraddon_pro", "available_avatars", 1, { cmid: cmid, userid: userid }),
                large: true,
            }
        ).then((modal) => {
            modal.show();
            // Handle form submission.
            modal.getRoot().on("click", ".assign-avatar-btn", (e) => {
                e.preventDefault()
                var form = e.currentTarget.querySelector("form");

                // Assign the avatar for the user.
                Ajax.call([
                    {
                        methodname: "mod_avatar_collect_avatar",
                        args: {
                            avatarid: form.querySelector('input[name="avatarid"]')?.value,
                            cmid: cmid,
                            userid: userid,
                        },
                        done: (response) => {
                            if (response.success) {
                                // Reload page to show updated avatar
                                window.location.reload()
                            } else {
                                Notification.addNotification({
                                    message: response.message,
                                    type: "error",
                                })
                            }
                        },
                        fail: Notification.exception,
                    },
                ])
            });

            modal.getRoot().on('click', '.upgrade-avatar-btn', (e) => {
                e.preventDefault()
                var form = e.currentTarget.querySelector("form");
                // Upgrade the avatar for the user.
                Ajax.call([
                    {
                        methodname: "mod_avatar_upgrade_avatar",
                        args: {
                            avatarid: form.querySelector('input[name="avatarid"]')?.value,
                            cmid: cmid,
                            userid: userid,
                        },
                        done: (response) => {
                            if (response.success) {
                                // Reload page to show updated avatar
                                window.location.reload()
                            } else {
                                Notification.addNotification({
                                    message: response.message,
                                    type: "error",
                                })
                            }
                        },
                        fail: Notification.exception,
                    },
                ])
            });

            return modal
        })
    }


    var loadUsage = (type, avatarid) => {

        return ModalFactory.create(
            {
                type: ModalFactory.types.DEFAULT,
                title: Str.get_string("avatarusage", "mod_avatar"),
                body: Fragment.loadFragment("mod_avatar", "avatar_usage", 1, { cmid: cmid, userid: userid }),
                large: true,
            }
        ).then((modal) => {
            modal.show();
        })

    }


    var init = (cmid) => {

        // Handle avatar assignment.
        document.querySelectorAll(SELECTORS.ASSIGN_AVATAR_BTN)?.forEach((assignBTN) => {
            assignBTN.addEventListener('click', (e) => {
                e.preventDefault();
                var userid = e.currentTarget.dataset.userid;
                assignAvatar(cmid, userid);
            });
        });

        /* document.querySelectorAll(SELECTORS.usageItem)?.forEach((usage) => {
            var avatarid = usage.dataset.avatarid;
            var type = usage.dataset.type;
            loadUsage(type, avatarid);
        }) */

        // Upgrade the avatar on click.
        document.querySelector(SELECTORS.UPGRADE_AVATAR_BTN)?.addEventListener('click', (e) => {
            e.preventDefault();
            var userid = e.currentTarget.dataset.userid;
            var avatarid = e.currentTarget.dataset.avatarid;

            // Upgrade the avatar for the user.
            Ajax.call([
                {
                    methodname: "mod_avatar_upgrade_avatar",
                    args: {
                        avatarid: avatarid,
                        cmid: cmid,
                        userid: userid,
                    },
                    done: (response) => {
                        if (response.success) {
                            // Reload page to show updated avatar.
                            window.location.reload()
                        } else {
                            Notification.addNotification({
                                message: response.message,
                                type: "error",
                            })
                        }
                    },
                    fail: Notification.exception,
                },
            ])
        });

    }

    return {
        init: init,
    }
})
