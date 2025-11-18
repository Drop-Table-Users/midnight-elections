<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Election - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: #0B4EA2;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        header h1 {
            font-size: 28px;
            font-weight: 600;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #0B4EA2;
            color: white;
        }

        .btn-primary:hover {
            background-color: #094080;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .card {
            background: white;
            border-radius: 6px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333;
        }

        input[type="text"],
        input[type="datetime-local"],
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #0B4EA2;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .error-list {
            margin: 10px 0 0 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0B4EA2;
            color: #0B4EA2;
        }

        .language-section {
            margin-bottom: 30px;
        }

        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #0B4EA2;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>{{ __('elections.admin.edit.title') }}</h1>
                <x-admin-language-switcher />
            </div>
        </div>
    </header>

    <div class="container">
        @if($errors->any())
            <div class="alert alert-error">
                <strong>{{ __('elections.admin.create.fix_errors') }}</strong>
                <ul class="error-list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($election->contract_address)
            <div class="info-box">
                <strong>{{ __('elections.admin.edit.note') }}</strong> {{ __('elections.admin.edit.blockchain_note') }}
            </div>
        @endif

        <div class="card">
            <form action="{{ route('admin.elections.update', $election->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="language-section">
                    <div class="section-title">{{ __('elections.admin.create.english_version') }}</div>

                    <div class="form-group">
                        <label for="title_en">{{ __('elections.admin.create.title_en') }}</label>
                        <input type="text" id="title_en" name="title_en" value="{{ old('title_en', $election->title_en) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="description_en">{{ __('elections.admin.create.description_en') }}</label>
                        <textarea id="description_en" name="description_en">{{ old('description_en', $election->description_en) }}</textarea>
                    </div>
                </div>

                <div class="language-section">
                    <div class="section-title">{{ __('elections.admin.create.slovak_version') }}</div>

                    <div class="form-group">
                        <label for="title_sk">{{ __('elections.admin.create.title_sk') }}</label>
                        <input type="text" id="title_sk" name="title_sk" value="{{ old('title_sk', $election->title_sk) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="description_sk">{{ __('elections.admin.create.description_sk') }}</label>
                        <textarea id="description_sk" name="description_sk">{{ old('description_sk', $election->description_sk) }}</textarea>
                    </div>
                </div>

                <div class="language-section">
                    <div class="section-title">{{ __('elections.admin.create.election_schedule') }}</div>

                    <div class="form-group">
                        <label for="start_date">{{ __('elections.admin.create.start_date') }}</label>
                        <input type="datetime-local" id="start_date" name="start_date"
                               value="{{ old('start_date', $election->start_date ? $election->start_date->format('Y-m-d\TH:i') : '') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">{{ __('elections.admin.create.end_date') }}</label>
                        <input type="datetime-local" id="end_date" name="end_date"
                               value="{{ old('end_date', $election->end_date ? $election->end_date->format('Y-m-d\TH:i') : '') }}" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('elections.admin.edit.submit') }}</button>
                    <a href="{{ route('admin.elections.show', $election->id) }}" class="btn btn-secondary">{{ __('elections.admin.create.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
