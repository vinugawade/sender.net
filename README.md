# Sender.net Integration Module

[![CircleCI](https://dl.circleci.com/status-badge/img/gh/vinugawade/sender.net/tree/master.svg?style=svg)](https://dl.circleci.com/status-badge/redirect/gh/vinugawade/sender.net/tree/master)

This module facilitates the integration of Drupal with the [Sender.net](https://www.sender.net) service.

## Table of Contents

- [Requirements](#requirements)
- [Recommended Modules](#recommended-modules)
- [Installation](#installation)
- [Configuration](#configuration)
- [Features](#features)
- [Maintainers](#maintainers)

## Requirements

- A [Sender.net](https://auth.sender.net/oauth/login) account
- [API access tokens](https://app.sender.net/settings/tokens) for your sender.net account

## Recommended Modules

There are no additional recommended modules required.

## Installation

1. Download the `Sender.net Integration` module and move it to
 your Drupal installation's module directory.
2. Navigate to `Administration > Extend` and install the module.

For more information, refer to [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

1. Obtain [API access tokens](https://app.sender.net/settings/tokens) from your [Sender.net](https://auth.sender.net/oauth/login) account and set them in the module's settings at `Configuration > System > Sender.net`.
2. Set up the `Base URL` from the [API documentation](https://api.sender.net).
3. Choose any available group if desired, and save the configuration.
4. Visit `Structure > Block Layout` to place the `Sender.net Subscription Block`
in the desired region.

## Features

- Add new email subscribers to the [sender.net](https://www.sender.net) service.
- Select available groups from the module settings.
- Add subscribers to specific groups.

## Maintainers

- [Vinay Gawade](https://www.drupal.org/u/vinaygawade)
