import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Box,
  Paper,
  Typography,
  TextField,
  Button,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Alert,
  SelectChangeEvent,
} from '@mui/material';
import { DatePicker } from '@mui/x-date-pickers/DatePicker';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDateFns } from '@mui/x-date-pickers/AdapterDateFns';
import { ja } from 'date-fns/locale';
import axiosInstance from '../lib/axios';

interface TaskForm {
  title: string;
  description: string;
  status: string;
  priority: string;
  due_date: Date | null;
}

const TaskCreate = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState<TaskForm>({
    title: '',
    description: '',
    status: 'not_started',
    priority: 'medium',
    due_date: new Date(),
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleSelectChange = (e: SelectChangeEvent) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleDateChange = (newValue: Date | null) => {
    setFormData(prev => ({
      ...prev,
      due_date: newValue,
    }));
  };

  const validateForm = () => {
    if (!formData.title.trim()) {
      setError('タイトルを入力してください');
      return false;
    }
    if (!formData.description.trim()) {
      setError('説明を入力してください');
      return false;
    }
    if (!formData.due_date) {
      setError('期限を設定してください');
      return false;
    }
    return true;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (!validateForm()) {
      return;
    }

    setLoading(true);

    try {
      await axiosInstance.post('/tasks', {
        ...formData,
        due_date: formData.due_date?.toISOString(),
      });

      navigate('/tasks');
    } catch (err) {
      setError('タスクの作成に失敗しました');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        新規タスク作成
      </Typography>

      <Paper elevation={2} sx={{ p: 3 }}>
        {error && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        )}

        <Box component="form" onSubmit={handleSubmit}>
          <TextField
            fullWidth
            required
            label="タイトル"
            name="title"
            value={formData.title}
            onChange={handleChange}
            margin="normal"
          />

          <TextField
            fullWidth
            required
            label="説明"
            name="description"
            value={formData.description}
            onChange={handleChange}
            margin="normal"
            multiline
            rows={4}
          />

          <Box sx={{ mt: 2, display: 'flex', gap: 2 }}>
            <FormControl fullWidth>
              <InputLabel>状態</InputLabel>
              <Select
                name="status"
                value={formData.status}
                label="状態"
                onChange={handleSelectChange}
              >
                <MenuItem value="not_started">未着手</MenuItem>
                <MenuItem value="in_progress">進行中</MenuItem>
                <MenuItem value="completed">完了</MenuItem>
              </Select>
            </FormControl>

            <FormControl fullWidth>
              <InputLabel>優先度</InputLabel>
              <Select
                name="priority"
                value={formData.priority}
                label="優先度"
                onChange={handleSelectChange}
              >
                <MenuItem value="low">低</MenuItem>
                <MenuItem value="medium">中</MenuItem>
                <MenuItem value="high">高</MenuItem>
              </Select>
            </FormControl>
          </Box>

          <Box sx={{ mt: 2 }}>
            <LocalizationProvider dateAdapter={AdapterDateFns} adapterLocale={ja}>
              <DatePicker
                label="期限"
                value={formData.due_date}
                onChange={handleDateChange}
                format="yyyy/MM/dd"
                slotProps={{
                  textField: {
                    fullWidth: true,
                    required: true,
                  },
                }}
              />
            </LocalizationProvider>
          </Box>

          <Box sx={{ mt: 3, display: 'flex', gap: 2 }}>
            <Button
              type="submit"
              variant="contained"
              disabled={loading}
              sx={{ flex: 1 }}
            >
              {loading ? '作成中...' : '作成'}
            </Button>
            <Button
              variant="outlined"
              onClick={() => navigate('/tasks')}
              sx={{ flex: 1 }}
            >
              キャンセル
            </Button>
          </Box>
        </Box>
      </Paper>
    </Box>
  );
};

export default TaskCreate;
