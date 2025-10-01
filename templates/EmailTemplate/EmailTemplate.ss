<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>$Subject</title>
    <% include EmailTemplate\Includes\EmailTemplateStyle %>
</head>
<body>
    <div class="wrapper">
        <table class="main" width="100%" cellpadding="0" cellspacing="0" role="presentation" align="center">
            <!-- Header with Logo -->
            <tr>
                <td class="header">
                    <% if $Logo %>
                        <img src="$Logo.AbsoluteURL" alt="$SiteConfig.Title" class="logo">
                    <% else %>
                        <h1>$SiteConfig.Title</h1>
                    <% end_if %>
                </td>
            </tr>

            <!-- Main Content -->
            <tr>
                <td class="content">
                    <% if $Greeting %>
                        <p>$Greeting</p>
                    <% else %>
                        <p>Hallo,</p>
                    <% end_if %>

                    <% if $Title %>
                        <h1>$Title</h1>
                    <% end_if %>

                    <% if $FormEntries %>
                        <table class="form-entries" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tbody>
                                <% loop $FormEntries %>
                                    <% if not $IsFreeText %>
                                        <tr>
                                            <th align="left" style="padding:4px 8px; vertical-align: top;">$Label</th>
                                            <td align="left" style="padding:4px 8px;">$Value</td>
                                        </tr>
                                    <% end_if %>
                                <% end_loop %>
                            </tbody>
                        </table>
                        <br>
                        <% loop $FormEntries %>
                            <% if $IsFreeText %>
                                <p><strong>$Label</strong><br />$Value</p>
                            <% end_if %>
                        <% end_loop %>
                    <% end_if %>

                    $EmailContent.RAW

                    <% if $CallToAction && $CallToActionLink %>
                        <div style="text-align: center;">
                            <a href="$CallToActionLink" class="button">$CallToAction</a>
                        </div>
                        <br>
                    <% end_if %>

                    <% if $Signature %>
                        <div class="divider"></div>
                        <p>$Signature</p>
                    <% else %>
                        <div class="divider"></div>
                        <p><%t EmailTemplate.GREETING 'Mit freundlichen Grüßen,' %></p>
                        <p>$SiteConfig.Title Team</p>
                    <% end_if %>
                </td>
            </tr>

            <!-- Footer -->
            <tr>
                <td class="footer">
                    <% if $FooterContent %>
                        $FooterContent
                    <% else %>
                        <p>&copy; $Now.Year {$SiteConfig.Title}. <%t EmailTemplate.COPYRIGHT 'Alle Rechte vorbehalten.' %></p>
                    <% end_if %>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
